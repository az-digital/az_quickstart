<?php

declare(strict_types=1);

namespace Drupal\actions_permissions\EventSubscriber;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Session\AccountInterface;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defines module event subscriber class.
 *
 * Alters actions to make use of permissions created by the module.
 */
final class ActionsPermissionsEventSubscriber implements EventSubscriberInterface {

  // Subscribe to the VBO event with low priority
  // to let other modules alter requirements first.
  private const PRIORITY = -999;

  /**
   * Constructor.
   */
  public function __construct(
    private readonly AccountInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ViewsBulkOperationsActionManager::ALTER_ACTIONS_EVENT][] = [
      'alterActions',
      self::PRIORITY,
    ];
    return $events;
  }

  /**
   * Alter the actions' definitions.
   *
   * @var \Drupal\Component\EventDispatcher\Event $event
   *   The event to respond to.
   */
  public function alterActions(Event $event): void {

    // Don't alter definitions if this is invoked by the
    // own permissions creating method.
    if (!empty($event->alterParameters['skip_actions_permissions'])) {
      return;
    }

    foreach ($event->definitions as $action_id => $definition) {

      // Only process actions that don't define their own requirements.
      if (empty($definition['requirements'])) {
        $permission_id = 'execute ' . $definition['id'];
        if (empty($definition['type'])) {
          $permission_id .= ' all';
        }
        else {
          $permission_id .= ' ' . $definition['type'];
        }
        if (!$this->currentUser->hasPermission($permission_id)) {
          unset($event->definitions[$action_id]);
        }
      }
    }
  }

}
