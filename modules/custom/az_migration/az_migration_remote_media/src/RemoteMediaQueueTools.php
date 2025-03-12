<?php

declare(strict_types=1);

namespace Drupal\az_migration_remote_media;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * @todo Add class description.
 */
final class RemoteMediaQueueTools {
  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Constructs a RemoteMediaQueueTools object.
   */
  public function __construct(
    QueueFactory $queue,
    MessengerInterface $messenger,
    AccountProxy $account,
  ) {
    $this->queueFactory = $queue;
    $this->messenger = $messenger;
    $this->currentUser = $account;
  }

  /**
   * @todo Add method description.
   */
  public function status(): void {
    // Output a status message if we have items in queue.
    $items = $this->queueFactory->get('az_deferred_media')->numberOfItems();
    if (!empty($items) && $this->currentUser->hasPermission('access remote media migration tools')) {
      $this->messenger->addWarning(t('@items remote media items are awaiting download. They will be downloaded shortly, or <a href=":batchroute">you may download them now.</a>.', [
        '@items' => $items,
        ':batchroute' => Url::fromRoute('az_migration_remote_media.batch')->toString(),
      ]));
    }
  }

  /**
   * @todo Add method description.
   */
  public function batch(): void {
    // Create a batch for our queue items.
    $batch_builder = (new BatchBuilder())->setTitle(t('Migrating Remote Media'))
      ->setFinishCallback([self::class, 'batchFinished']);
    $queue = $this->queueFactory->get('az_deferred_media');
    $id = 0;
    // Claim the items in the queue. They will be replaced if our lease expires.
    while ($item = $queue->claimItem()) {
      $batch_builder->addOperation([self::class, 'batchProcess'], [
        $id, [$item],
      ]);
      $id++;
    }
    batch_set($batch_builder->toArray());
  }

  /**
   * @todo Add method description.
   */
  public static function batchProcess(int $batchId, array $chunk, array &$context): void {

    // Initialize necessary services.
    $factory = \Drupal::service('queue');
    $queue = \Drupal::service('queue')->get('az_deferred_media');
    $manager = \Drupal::service('plugin.manager.queue_worker');
    $worker = $manager->createInstance('az_deferred_media');

    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
    }

    $urls = [];

    // Process the individual queue items using the worker plugin.
    foreach ($chunk as $item) {
      try {
        // Record the url if we had one.
        if (!empty($item->data['url'])) {
          $urls[] = $item->data['url'];
        }
        $worker->processItem($item->data);
        $queue->deleteItem($item);
      }
      catch (\Exception $e) {
      }

    }
    $urls = implode(', ', $urls);

    // Inform what urls are being fetched:
    $context['message'] = t('Fetching @urls ...', [
      '@urls' => $urls,
    ]);
    sleep(5);
  }

  /**
   * @todo Add method description.
   */
  public static function batchFinished(bool $success, array $results, array $operations, string $elapsed): void {
    // @todo add implementation.
  }

}
