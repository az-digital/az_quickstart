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
 *
 * This approach is compatible with Drupal's render caching because #attached
 * metadata survives caching — the subscriber sees entries from both cached
 * and freshly rendered accordions.
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

    // Collect all faq_questions_* entries and their questions.
    $all_questions = [];
    $remaining_head = [];

    foreach ($attachments['html_head'] as $item) {
      [$element, $key] = $item;
      if (str_starts_with($key, 'faq_questions_')) {
        // Decode the questions from the JSON value.
        $data = json_decode($element['#value'], TRUE);
        if (is_array($data) && !empty($data['questions'])) {
          foreach ($data['questions'] as $question) {
            $all_questions[] = $question;
          }
        }
      }
      else {
        $remaining_head[] = $item;
      }
    }

    if (empty($all_questions)) {
      return;
    }

    // Build the single merged FAQPage schema.
    $faq_schema = [
      '@context' => 'https://schema.org',
      '@type' => 'FAQPage',
    ];

    // Sort questions to match their visual order on the page.
    // Decode HTML entities in the response so that encoded characters
    // (e.g. &#039; for apostrophes) are converted back to their raw form,
    // allowing a direct match against the raw question titles.
    $html = html_entity_decode($response->getContent(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    usort($all_questions, function (array $a, array $b) use ($html): int {
      $pos_a = mb_strpos($html, $a['name']);
      $pos_b = mb_strpos($html, $b['name']);
      if ($pos_a === FALSE) {
        $pos_a = PHP_INT_MAX;
      }
      if ($pos_b === FALSE) {
        $pos_b = PHP_INT_MAX;
      }
      return $pos_a <=> $pos_b;
    });

    $faq_schema['mainEntity'] = $all_questions;

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
