<?php

namespace Drupal\az_core;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Service for tracking and reporting content updates across modules.
 */
final class AZContentUpdateTracker {
  use StringTranslationTrait;

  /**
   * Constructs a new AZContentUpdateTracker.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreInterface $storage
   *   The key value store for content updates.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter service.
   */
  public function __construct(
    protected readonly KeyValueStoreInterface $storage,
    protected readonly TimeInterface $time,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly DateFormatterInterface $dateFormatter,
  ) {}

  /**
   * Records a database update with batched entities.
   *
   * @param string $update_id
   *   Identifier for this update (typically the update hook function name).
   * @param array $entities
   *   Array of entities that were updated, keyed by entity type.
   *   Example structure:
   *   [
   *     'node' => [
   *       123 => [
   *         'type' => 'page',
   *         'fields' => ['field_paragraphs'],
   *       ],
   *     ],
   *     'block_content' => [
   *       456 => [
   *         'type' => 'basic',
   *         'fields' => ['field_content'],
   *       ],
   *     ],
   *   ].
   */
  public function recordBatchUpdate($update_id, array $entities) {
    foreach ($entities as $entity_type_id => $items) {
      foreach ($items as $entity_id => $details) {
        $this->recordUpdate($update_id, $entity_type_id, $entity_id, $details);
      }
    }
  }

  /**
   * Records an update to an entity.
   *
   * @param string $update_id
   *   Identifier for this update (typically the update hook function name).
   * @param string $entity_type_id
   *   The entity type ID (e.g., 'node', 'block_content').
   * @param string $entity_id
   *   The entity ID.
   * @param array $details
   *   Additional details about the update. Should include:
   *   - type: The bundle type
   *   - fields: Array of field names that were updated
   *   Note: The count will be maintained internally by this service.
   */
  public function recordUpdate($update_id, $entity_type_id, $entity_id, array $details) {
    $key = "az:{$update_id}:{$entity_type_id}:{$entity_id}";
    $existing = $this->storage->get($key);

    // If this entity has been updated before, merge the details.
    if ($existing) {
      // Increment the count for each update.
      $details['count'] = ($existing['count'] ?? 0) + 1;
      // Merge field information if present.
      if (isset($details['fields']) && isset($existing['fields'])) {
        $details['fields'] = array_merge($existing['fields'], $details['fields']);
      }
      $details = array_merge($existing, $details);
    }
    else {
      // First update for this entity.
      $details['count'] = 1;
    }

    $this->storage->set($key, $details + [
      'update_id' => $update_id,
      'entity_type' => $entity_type_id,
      'id' => $entity_id,
      'timestamp' => $this->time->getRequestTime(),
    ]);
  }

  /**
   * Gets all recorded updates.
   *
   * @param string $update_id
   *   Optional update ID to filter by.
   *
   * @return array
   *   Array of updates, grouped by entity type and ID, with each entity's
   *   updates listed under it.
   */
  public function getUpdates($update_id = NULL) {
    $updates = [];
    $all_updates = $this->storage->getAll();

    foreach ($all_updates as $key => $details) {
      if ($update_id && $details['update_id'] !== $update_id) {
        continue;
      }

      $entity_type = $details['entity_type'];
      $entity_id = $details['id'];
      $update_id = $details['update_id'];

      if (!isset($updates[$entity_type])) {
        $updates[$entity_type] = [];
      }
      if (!isset($updates[$entity_type][$entity_id])) {
        $updates[$entity_type][$entity_id] = [
          'type' => $details['type'],
          'total_count' => 0,
          'fields' => [],
          'updates' => [],
        ];
      }

      // Track all unique fields and total count.
      $updates[$entity_type][$entity_id]['total_count'] += ($details['count'] ?? 0);
      if (!empty($details['fields'])) {
        $updates[$entity_type][$entity_id]['fields'] = array_unique(
          array_merge($updates[$entity_type][$entity_id]['fields'], $details['fields'])
        );
      }

      // Store individual update details.
      $updates[$entity_type][$entity_id]['updates'][$update_id] = [
        'count' => $details['count'] ?? 0,
        'fields' => $details['fields'] ?? [],
        'timestamp' => $details['timestamp'],
      ];
    }

    return $updates;
  }

  /**
   * Builds a render array for the content updates report.
   *
   * @return array
   *   A render array.
   */
  public function buildReport() {
    $updates = $this->getUpdates();

    if (empty($updates)) {
      return [
        '#markup' => $this->t('No content updates have been recorded.'),
      ];
    }

    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['az-content-updates-report']],
    ];

    $table = [
      '#type' => 'table',
      '#header' => [
        $this->t('Title'),
        $this->t('Type'),
        $this->t('Total Updates'),
        $this->t('Fields Updated'),
        $this->t('Update Details'),
        $this->t('Operations'),
      ],
    ];

    // Process all entity types.
    foreach ($updates as $entity_type_id => $entities) {
      try {
        $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);
        $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      }
      catch (\Exception $e) {
        // Skip if we can't load the entity type.
        continue;
      }

      foreach ($entities as $entity_id => $info) {
        /** @var \Drupal\Core\Entity\EntityInterface|null $entity */
        $entity = $entity_storage->load($entity_id);
        if (!$entity) {
          // Skip if we can't load the entity.
          continue;
        }

        // Build operation links based on available entity routes.
        $operations = [];

        if ($entity->hasLinkTemplate('canonical')) {
          $operations[] = Link::fromTextAndUrl(
            $this->t('View'),
            $entity->toUrl('canonical')
          );
        }

        if ($entity->hasLinkTemplate('edit-form')) {
          $operations[] = Link::fromTextAndUrl(
            $this->t('Edit'),
            $entity->toUrl('edit-form')
          );
        }

        // Build the update details as an item list.
        $update_details = array_map(
          fn($update_id, $data) => $this->t('@type: @count updates to @fields (@date)', [
            '@type' => $this->getUpdateTypeLabel($update_id),
            '@count' => $data['count'],
            '@fields' => $this->formatFieldsList(['fields' => $data['fields']]),
            '@date' => $this->dateFormatter->format($data['timestamp']),
          ]),
          array_keys($info['updates']),
          array_values($info['updates'])
        );

        $table['#rows'][] = [
          Link::fromTextAndUrl($entity->label(), $entity->toUrl()),
          $this->t('@type - @bundle', [
            '@type' => $entity_type->getLabel(),
            '@bundle' => $info['type'],
          ]),
          $info['total_count'],
          $this->formatFieldsList(['fields' => $info['fields']]),
          [
            'data' => [
              '#theme' => 'item_list',
              '#items' => $update_details,
              '#attributes' => ['class' => ['az-content-update-details']],
            ],
          ],
          [
            'data' => [
              '#type' => 'operations',
              '#links' => array_map(
                fn(Link $link) => [
                  'title' => $link->getText(),
                  'url' => $link->getUrl(),
                ],
                $operations
              ),
            ],
          ],
        ];
      }
    }

    $build['content'] = $table;

    return $build;
  }

  /**
   * Gets a human-readable label for an update type.
   *
   * @param string $update_id
   *   The update identifier.
   *
   * @return string
   *   The human-readable label.
   */
  protected function getUpdateTypeLabel($update_id) {
    $labels = [
      'az_paragraphs_update_1130001' => $this->t('Bootstrap 5 Paragraph Updates'),
      'az_content_bs5' => $this->t('Bootstrap 5 Content Field Updates'),
    ];

    return $labels[$update_id] ?? $update_id;
  }

  /**
   * Formats the list of updated fields for display.
   *
   * @param array $info
   *   The entity update information.
   *
   * @return string
   *   Formatted list of fields.
   */
  protected function formatFieldsList(array $info) {
    if (empty($info['fields'])) {
      return '';
    }

    return implode(', ', array_unique($info['fields']));
  }

}
