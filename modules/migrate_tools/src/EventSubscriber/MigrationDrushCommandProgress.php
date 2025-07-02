<?php

declare(strict_types = 1);

namespace Drupal\migrate_tools\EventSubscriber;

use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Plugin\MigrationInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Import and rollback progress bar.
 */
class MigrationDrushCommandProgress implements EventSubscriberInterface {

  protected LoggerInterface $logger;
  protected ?ProgressBar $symfonyProgressBar = NULL;

  /**
   * MigrationDrushCommandProgress constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    $events[MigrateEvents::POST_ROW_SAVE][] = ['updateProgressBar', -10];
    $events[MigrateEvents::MAP_DELETE][] = ['updateProgressBar', -10];
    $events[MigrateEvents::POST_IMPORT][] = ['clearProgress', 10];
    $events[MigrateEvents::POST_ROLLBACK][] = ['clearProgress', 10];
    return $events;
  }

  /**
   * Initializes the progress bar.
   *
   * This must be called before the progress bar can be used.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration.
   * @param array $options
   *   Additional options of the command.
   */
  public function initializeProgress(OutputInterface $output, MigrationInterface $migration, array $options = []): void {
    // Don't display progress bar if explicitly disabled.
    if (!empty($migration->skipProgressBar)) {
      return;
    }
    // If the source is configured to skip counts, a progress bar is not
    // possible.
    if (!empty($migration->getSourceConfiguration()['skip_count'])) {
      return;
    }
    try {
      // Clone so that any generators aren't initialized prematurely.
      $source = clone $migration->getSourcePlugin();
      $count = (int) $source->count();
      // In case the --limit option is set, reduce the count.
      if (array_key_exists('limit', $options) && $options['limit'] > 0 && $options['limit'] < $count) {
        $count = (int) $options['limit'];
      }
      $this->symfonyProgressBar = new ProgressBar($output, $count);
    }
    catch (\Exception $exception) {
      if (!empty($migration->continueOnFailure)) {
        $this->logger->error($exception->getMessage());
      }
      else {
        throw $exception;
      }
    }
  }

  /**
   * Event callback for advancing the progress bar.
   */
  public function updateProgressBar(): void {
    if ($this->isProgressBar()) {
      $this->symfonyProgressBar->advance();
    }
  }

  /**
   * Event callback for removing the progress bar after operation is finished.
   */
  public function clearProgress(): void {
    if ($this->isProgressBar()) {
      $this->symfonyProgressBar->clear();
    }
  }

  /**
   * Determine if a progress bar should be displayed.
   *
   * @return bool
   *   TRUE if a progress bar should be displayed, FALSE otherwise.
   */
  protected function isProgressBar(): bool {
    // Can't do anything if the progress bar is not initialised; this probably
    // means we're not running as a Drush command, therefore do nothing.
    if ($this->symfonyProgressBar === NULL) {
      return FALSE;
    }
    return TRUE;
  }

}
