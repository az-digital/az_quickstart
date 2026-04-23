<?php

declare(strict_types=1);

namespace Drupal\az_accordion\EventSubscriber;

use Drupal\Core\Render\HtmlResponse;
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
 */
class AZFaqAggregatorSubscriber implements EventSubscriberInterface {

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

    // Sort groups by the DOM order of each accordion wrapper's id attribute.
    // Non-FAQ accordions that match this pattern are ignored since uksort
    // only consults IDs that keyed an FAQ accordion group.
    preg_match_all('/id="accordion-(\d+)/', $response->getContent(), $matches);
    $order = array_flip($matches[1]);

    uksort($groups, function ($a, $b) use ($order): int {
      $pa = $order[$a] ?? PHP_INT_MAX;
      $pb = $order[$b] ?? PHP_INT_MAX;
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

}
