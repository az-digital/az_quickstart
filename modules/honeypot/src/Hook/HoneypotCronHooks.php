<?php

declare(strict_types=1);

namespace Drupal\honeypot\Hook;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations used for scheduled execution.
 */
final class HoneypotCronHooks {

  /**
   * Constructs a new HoneypotCronHooks service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config.factory service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Component\Datetime\TimeInterface $timeService
   *   The datetime.time service.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected Connection $connection,
    protected TimeInterface $timeService,
  ) {}

  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron(): void {
    // Delete {honeypot_user} entries older than the value of 'expire'.
    $expire_limit = $this->configFactory->get('honeypot.settings')->get('expire');
    $this->connection->delete('honeypot_user')
      ->condition('timestamp', $this->timeService->getRequestTime() - $expire_limit, '<')
      ->execute();
  }

}
