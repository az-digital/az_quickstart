<?php

declare(strict_types=1);

namespace Drupal\migmag_rollbackable\Plugin\migrate\destination;

use Drupal\migmag_rollbackable\Traits\RollbackableTrait;
use Drupal\migrate\Plugin\migrate\destination\Config;
use Drupal\migrate\Row;

/**
 * Provides rollbackable configuration destination plugin.
 *
 * @see \Drupal\migrate\Plugin\migrate\destination\Config
 *
 * @MigrateDestination(
 *   id = "migmag_rollbackable_config"
 * )
 */
final class RollbackableConfig extends Config {

  use RollbackableTrait;

  /**
   * {@inheritdoc}
   */
  protected $supportsRollback = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function getTargetObjectId($row_or_destination_ids): string {
    return $this->configuration['config_name'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getTargetLangcode($row_or_destination_ids): string {
    if (!$this->isTranslationDestination()) {
      return '';
    }

    if ($row_or_destination_ids instanceof Row) {
      return $row_or_destination_ids->getDestinationProperty('langcode');
    }

    return $row_or_destination_ids['langcode'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getTargetObject(string $target_object_id, string $langcode = '') {
    return $this->isTranslationDestination()
      ? $this->language_manager->getLanguageConfigOverride($langcode, $target_object_id)
      : $this->config;
  }

}
