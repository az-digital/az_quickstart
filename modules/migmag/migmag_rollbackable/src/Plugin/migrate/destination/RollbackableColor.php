<?php

declare(strict_types=1);

namespace Drupal\migmag_rollbackable\Plugin\migrate\destination;

use Drupal\color\Plugin\migrate\destination\Color;
use Drupal\migmag_rollbackable\Traits\RollbackableTrait;
use Drupal\migrate\Row;

/**
 * Rollbackable destination plugin for theme color settings migrations.
 *
 * @see \Drupal\color\Plugin\migrate\destination\Color
 *
 * @MigrateDestination(
 *   id = "migmag_rollbackable_color",
 *   provider = "color"
 * )
 */
final class RollbackableColor extends Color {

  use RollbackableTrait;

  /**
   * {@inheritdoc}
   */
  protected $supportsRollback = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'config_name' => ['type' => 'string'],
      'component' => ['type' => 'string'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getTargetObjectId($row_or_destination_ids): string {
    if ($row_or_destination_ids instanceof Row) {
      return $row_or_destination_ids->getDestinationProperty('configuration_name');
    }
    return $row_or_destination_ids['config_name'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getTargetComponent($row_or_destination_ids): string {
    if ($row_or_destination_ids instanceof Row) {
      return $row_or_destination_ids->getDestinationProperty('element_name');
    }
    return $row_or_destination_ids['component'];
  }

  /**
   * {@inheritdoc}
   */
  protected function generateDestinationIds($parent_destination_ids, string $target_object_id, string $component, string $langcode): array {
    return [$target_object_id, $component];
  }

}
