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

      if (empty($attachments['drupalSettings']['az_accordion'])) {
        return;
      }

      // If az_accordion is an associative array, get all values.
      $accordion_settings = $attachments['drupalSettings']['az_accordion'];
      if (!is_array($accordion_settings)) {
        $accordion_settings = [$accordion_settings];
      }
      // If associative, get values.
      if (array_keys($accordion_settings) !== range(0, count($accordion_settings) - 1)) {
        $accordion_settings = array_values($accordion_settings);
      }

    // Aggregate FAQ questions from drupalSettings['az_accordion'].
    $all_questions = [];
    foreach ($accordion_settings as $settings) {
      if (!empty($settings['faq']) && !empty($settings['items'])) {
        foreach ($settings['items'] as $item) {
          // Only add items with both title and body.
          if (!empty($item['title']) && !empty($item['body'])) {
            $all_questions[] = [
              '@type' => 'Question',
              'name' => strip_tags($item['title']),
              'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $item['body'],
              ],
            ];
          }
        }
      }
    }

    if (empty($all_questions)) {
      return;
    }

    // Build the single merged FAQPage schema.
    $faq_schema = [
      '@context' => 'https://schema.org',
      '@type' => 'FAQPage',
      'mainEntity' => $all_questions,
    ];

    // Replace html_head with the merged FAQPage schema.
    $attachments['html_head'][] = [
      [
        '#type' => 'html_tag',
        '#tag' => 'script',
        '#attributes' => ['type' => 'application/ld+json'],
        '#value' => json_encode($faq_schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
      ],
      'faq_schema',
    ];
    $response->setAttachments($attachments);
  }

}
