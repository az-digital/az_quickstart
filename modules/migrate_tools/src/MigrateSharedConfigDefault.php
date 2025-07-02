<?php

declare(strict_types = 1);

namespace Drupal\migrate_tools;

use Drupal\Core\Plugin\PluginBase;

/**
 * Default class used for migrate_shared_configuration plugins.
 */
final class MigrateSharedConfigDefault extends PluginBase implements MigrateSharedConfigInterface {

  /**
   * {@inheritdoc}
   */
  public function id(): string {
    return $this->pluginDefinition['id'];
  }

}
