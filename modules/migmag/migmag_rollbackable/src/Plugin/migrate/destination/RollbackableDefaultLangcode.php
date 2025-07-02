<?php

declare(strict_types=1);

namespace Drupal\migmag_rollbackable\Plugin\migrate\destination;

use Drupal\language\Plugin\migrate\destination\DefaultLangcode;
use Drupal\migmag_rollbackable\Traits\RollbackableTrait;
use Drupal\migrate\Row;

/**
 * Provides rollbackable default_langcode destination plugin.
 *
 * @see \Drupal\language\Plugin\migrate\destination\DefaultLangcode
 *
 * @MigrateDestination(
 *   id = "migmag_rollbackable_default_langcode",
 *   provider = "language"
 * )
 */
final class RollbackableDefaultLangcode extends DefaultLangcode {

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
   *
   * @return \Drupal\Core\Config\StorableConfigBase
   *   The target object (a config).
   */
  protected function getTargetObject(string $target_object_id, string $langcode = '') {
    return $this->isTranslationDestination()
      ? $this->language_manager->getLanguageConfigOverride($langcode, $target_object_id)
      : $this->config;
  }

}
