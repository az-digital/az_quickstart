<?php

declare(strict_types=1);

namespace Drupal\migmag_rollbackable\Plugin\migrate\destination;

use Drupal\migmag_rollbackable\Traits\RollbackableTrait;
use Drupal\migrate\Row;
use Drupal\system\Plugin\migrate\destination\d7\ThemeSettings;

/**
 * Persist rollbackable Drupal 7 theme settings to the config system.
 *
 * @see \Drupal\system\Plugin\migrate\destination\d7\ThemeSettings
 *
 * @MigrateDestination(
 *   id = "migmag_rollbackable_theme_settings"
 * )
 */
final class RollbackableThemeSettings extends ThemeSettings {

  use RollbackableTrait;

  /**
   * {@inheritdoc}
   */
  protected $supportsRollback = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function getTargetObjectId($row_or_destination_ids): string {
    // Compatibility with patch at https://drupal.org/i/3096972
    if (isset($this->configuration['theme'])) {
      return implode('.', [
        $this->configuration['theme'],
        'settings',
      ]);
    }

    if ($row_or_destination_ids instanceof Row) {
      return $row_or_destination_ids->getDestinationProperty('configuration_name');
    }

    return $row_or_destination_ids['name'];
  }

  /**
   * {@inheritdoc}
   */
  protected function generateDestinationIds($parent_destination_ids, string $target_object_id, string $component, string $langcode): array {
    return [$target_object_id];
  }

  /**
   * {@inheritdoc}
   */
  protected function getMappedKey(string $destination_key): ?string {
    switch ($destination_key) {
      case 'default_logo':
        return 'logo.use_default';

      case 'logo_path':
        return 'logo.path';

      case 'default_favicon':
        return 'favicon.use_default';

      case 'favicon_path':
        return 'favicon.path';

      case 'favicon_mimetype':
        return 'favicon.mimetype';

      default:
        if (substr($destination_key, 0, 7) == 'toggle_') {
          return 'features.' . mb_substr($destination_key, 7);
        }
        else {
          return !in_array($destination_key, ['theme', 'logo_upload'], TRUE)
            ? $destination_key
            : NULL;
        }
    }
  }

}
