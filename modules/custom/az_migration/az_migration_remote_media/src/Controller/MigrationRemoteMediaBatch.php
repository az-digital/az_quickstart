<?php

declare(strict_types=1);

namespace Drupal\az_migration_remote_media\Controller;

use Drupal\az_migration_remote_media\RemoteMediaQueueTools;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for Quickstart Migration Remote Media routes.
 */
final class MigrationRemoteMediaBatch extends ControllerBase {

  /**
   * @var \Drupal\az_migration_remote_media\RemoteMediaQueueTools
   */
  protected $remoteMediaQueueTools;

  /**
   * The controller constructor.
   */
  public function __construct(RemoteMediaQueueTools $remoteMediaQueueTools) {
    $this->remoteMediaQueueTools = $remoteMediaQueueTools;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('az_migration_remote_media.tools'),
    );
  }

  /**
   * Run the batch.
   */
  public function __invoke(): RedirectResponse {
    $this->remoteMediaQueueTools->batch();
    return batch_process('admin/content/media');
  }

}
