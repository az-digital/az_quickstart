<?php

declare(strict_types = 1);

namespace Drupal\migrate_tools;

/**
 * Interface for migrate_shared_configuration plugins.
 */
interface MigrateSharedConfigInterface {

  /**
   * Returns the ID.
   *
   * @return string
   *   The shared configuration ID.
   */
  public function id(): string;

}
