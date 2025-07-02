<?php

declare(strict_types=1);

namespace Drupal\migmag_callback_upgrade;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\Callback;
use Drupal\migrate\Row;

/**
 * Replacement plugin class for the 'callback' migration process plugin.
 *
 * Provides the 'unpack_source' option for core versions lower than 9.2.0.
 *
 * @see https://drupal.org/node/3205079
 */
class MigMagCallback extends Callback {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!empty($this->configuration['unpack_source'])) {
      if (!is_array($value)) {
        throw new MigrateException(sprintf("When 'unpack_source' is set, the source must be an array. Instead it was of type '%s'", gettype($value)));
      }
      return call_user_func_array($this->configuration['callable'], $value);
    }
    return parent::transform($value, $migrate_executable, $row, $destination_property);
  }

}
