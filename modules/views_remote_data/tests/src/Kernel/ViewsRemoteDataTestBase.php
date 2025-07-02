<?php

declare(strict_types=1);

namespace Drupal\Tests\views_remote_data\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\views_remote_data\Events\RemoteDataLoadEntitiesEvent;
use Drupal\views_remote_data\Events\RemoteDataQueryEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Base test class for views_remote_data.
 */
abstract class ViewsRemoteDataTestBase extends EntityKernelTestBase implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'views',
    'views_remote_data',
  ];

  /**
   * Captured events.
   *
   * @var \Drupal\views_remote_data\Events\RemoteDataQueryEvent[]|RemoteDataLoadEntitiesEvent[]
   */
  protected $caughtEvents = [];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);
    $container
      ->register('testing.views_remote_data_subscriber', self::class)
      ->addTag('event_subscriber');
    $container->set('testing.views_remote_data_subscriber', $this);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      RemoteDataQueryEvent::class => 'onQuery',
      RemoteDataLoadEntitiesEvent::class => 'onLoadEntities',
    ];
  }

  /**
   * Handle the event.
   *
   * @param \Drupal\views_remote_data\Events\RemoteDataLoadEntitiesEvent $event
   *   The event.
   */
  public function onLoadEntities(RemoteDataLoadEntitiesEvent $event): void {
    $this->caughtEvents[] = $event;
  }

  /**
   * Handle the event.
   *
   * @param \Drupal\views_remote_data\Events\RemoteDataQueryEvent $event
   *   The event.
   */
  public function onQuery(RemoteDataQueryEvent $event): void {
    $this->caughtEvents[] = $event;
  }

}
