<?php

declare(strict_types = 1);

namespace Drupal\migrate_tools;

use Drupal\migrate\MigrateMessageInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Print message in drush from migrate message. Drush 9 version.
 *
 * @package Drupal\migrate_tools
 */
class Drush9LogMigrateMessage implements MigrateMessageInterface, LoggerAwareInterface {

  use LoggerAwareTrait;

  /**
   * The map between migrate status and drush log levels.
   *
   * @var array
   */
  protected array $map = [
    'status' => 'notice',
  ];

  /**
   * DrushLogMigrateMessage constructor.
   */
  public function __construct(LoggerInterface $logger) {
    $this->setLogger($logger);
  }

  /**
   * Output a message from the migration.
   *
   * @param string $message
   *   The message to display.
   * @param string $type
   *   The type of message to display.
   */
  public function display($message, $type = 'status'): void {
    $type = $this->map[$type] ?? $type;
    $this->logger->log($type, $message);
  }

}
