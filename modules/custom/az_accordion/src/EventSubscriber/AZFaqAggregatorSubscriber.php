<?php

declare(strict_types=1);

namespace Drupal\az_accordion\EventSubscriber;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Aggregates per-accordion FAQ question entries into a single FAQPage block.
 *
 * Each accordion formatter attaches its questions as a separate html_head
 * entry with a key prefixed by 'faq_questions_'. This subscriber runs before
 * HtmlResponseSubscriber (which processes attachments into HTML), finds all
 * such entries, merges the questions, and replaces them with a single
 * 'faq_schema' entry containing one FAQPage JSON-LD block.
 *
 * Group order matches authored page order, derived from the node's paragraph
 * field: direct accordion paragraphs contribute their own id; text paragraphs
 * are scanned for `<drupal-entity>` embeds whose flex blocks are inspected for
 * accordion paragraphs one level deep.
 */
class AZFaqAggregatorSubscriber implements EventSubscriberInterface {

  public function __construct(
    protected RouteMatchInterface $routeMatch,
    protected EntityRepositoryInterface $entityRepository,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Run before HtmlResponseSubscriber (priority 0) so we can modify
    // the raw attachments before they are processed into HTML.
    $events[KernelEvents::RESPONSE][] = ['onRespond', 10];
    return $events;
  }

  /**
   * Aggregates FAQ question html_head entries into a single FAQPage block.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The response event.
   */
  public function onRespond(ResponseEvent $event): void {
    $response = $event->getResponse();
    if (!$response instanceof HtmlResponse) {
      return;
    }

    $attachments = $response->getAttachments();
    if (empty($attachments['html_head'])) {
      return;
    }

    // Collect questions grouped by the accordion entity ID encoded in the
    // attachment key. Grouping lets us sort whole accordions by their
    // position on the page.
    $groups = [];
    $remaining_head = [];

    foreach ($attachments['html_head'] as $item) {
      [$element, $key] = $item;
      if (str_starts_with($key, 'faq_questions_')) {
        $entity_id = substr($key, strlen('faq_questions_'));
        $data = json_decode($element['#value'], TRUE);
        if (is_array($data) && !empty($data['questions'])) {
          $groups[$entity_id] = $data['questions'];
        }
      }
      else {
        $remaining_head[] = $item;
      }
    }

    if (empty($groups)) {
      return;
    }

    // Sort groups by authored order extracted from the node's paragraph tree.
    // Accordions not found in the tree (e.g. on non-node routes, or rendered
    // outside the main paragraph field) sort to the end via PHP_INT_MAX.
    $position = array_flip($this->buildAccordionOrder());
    uksort($groups, function ($a, $b) use ($position): int {
      $pa = $position[$a] ?? PHP_INT_MAX;
      $pb = $position[$b] ?? PHP_INT_MAX;
      return $pa <=> $pb;
    });

    $all_questions = [];
    foreach ($groups as $questions) {
      foreach ($questions as $question) {
        $all_questions[] = $question;
      }
    }

    // Build the single merged FAQPage schema.
    $faq_schema = [
      '@context' => 'https://schema.org',
      '@type' => 'FAQPage',
      'mainEntity' => $all_questions,
    ];

    // Add the merged schema as a single html_head entry.
    $remaining_head[] = [
      [
        '#type' => 'html_tag',
        '#tag' => 'script',
        '#attributes' => ['type' => 'application/ld+json'],
        '#value' => json_encode($faq_schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
      ],
      'faq_schema',
    ];

    $attachments['html_head'] = $remaining_head;
    $response->setAttachments($attachments);
  }

  /**
   * Returns accordion paragraph ids in authored page order for this request.
   *
   * Covers two cases:
   *   1. An accordion paragraph added directly to the node.
   *   2. A flex block embedded in a text paragraph on the node, where the
   *      flex block's paragraph field contains accordion paragraphs.
   *
   * @return string[]
   *   Accordion paragraph ids in authored order, as strings matching the
   *   entity-id form encoded in faq_questions_* attachment keys.
   */
  protected function buildAccordionOrder(): array {
    $node = $this->routeMatch->getParameter('node');
    if (!$node instanceof NodeInterface || !$node->hasField('field_az_main_content')) {
      return [];
    }

    $order = [];

    foreach ($node->get('field_az_main_content') as $item) {
      try {
        assert($item instanceof EntityReferenceItem);
      }
      catch (\AssertionError) {
        continue;
      }
      $paragraph = $item->entity;
      if (!$paragraph instanceof ContentEntityInterface) {
        continue;
      }

      // Case 1: accordion paragraph directly on the node.
      if ($paragraph->hasField('field_az_accordion')) {
        $order[] = (string) $paragraph->id();
        continue;
      }

      // Case 2: text paragraph whose body embeds a flex block that contains
      // accordion paragraphs in its own paragraph field.
      if (!$paragraph->hasField('field_az_text_area')) {
        continue;
      }
      foreach ($paragraph->get('field_az_text_area') as $text_item) {
        foreach ($this->parseEmbeddedEntities($text_item->value ?? '') as $embedded) {
          if (!$embedded->hasField('field_az_main_content')) {
            continue;
          }
          foreach ($embedded->get('field_az_main_content') as $nested_item) {
            try {
              assert($nested_item instanceof EntityReferenceItem);
            }
            catch (\AssertionError) {
              continue;
            }
            $nested = $nested_item->entity;
            if ($nested instanceof ContentEntityInterface && $nested->hasField('field_az_accordion')) {
              $order[] = (string) $nested->id();
            }
          }
        }
      }
    }

    return $order;
  }

  /**
   * Parses <drupal-entity> tags from CKEditor HTML and returns loaded entities.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   Loaded content entities found in the HTML, keyed by UUID.
   */
  protected function parseEmbeddedEntities(string $html): array {
    if (!str_contains($html, 'drupal-entity')) {
      return [];
    }
    if (!preg_match_all('/<drupal-entity\b[^>]*>/i', $html, $tag_matches)) {
      return [];
    }
    $entities = [];
    foreach ($tag_matches[0] as $tag) {
      if (!preg_match('/\bdata-entity-type="([^"]+)"/', $tag, $type_match) ||
          !preg_match('/\bdata-entity-uuid="([^"]+)"/', $tag, $uuid_match)) {
        continue;
      }
      $entity = $this->entityRepository->loadEntityByUuid($type_match[1], $uuid_match[1]);
      if ($entity instanceof ContentEntityInterface) {
        $entities[] = $entity;
      }
    }
    return $entities;
  }

}
