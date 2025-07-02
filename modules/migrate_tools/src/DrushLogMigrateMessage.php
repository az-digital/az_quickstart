<?php

declare(strict_types = 1);

namespace Drupal\migrate_tools;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateMessageInterface;

/**
 * Logger implementation for drush.
 *
 * @package Drupal\migrate_tools
 */
class DrushLogMigrateMessage extends MigrateMessage implements MigrateMessageInterface {

  /**
   * Output a message from the migration.
   *
   * @param string $message
   *   The message to display.
   * @param string $type
   *   The type of message to display.
   *
   * @see drush_log()
   */
  public function display($message, $type = 'status'): void {
    $type = $this->map[$type] ?? RfcLogLevel::NOTICE;
    \Drupal::service(('logger.channel.migrate_tools'))->log($type, $message);
  }

}
