<?php

namespace Drupal\cas\Subscriber;

use Drupal\cas\Event\CasPreRegisterEvent;
use Drupal\cas\Service\CasHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a CasAutoAssignRoleSubscriber.
 */
class CasAutoAssignRolesSubscriber implements EventSubscriberInterface {

  /**
   * Stores settings object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $settings;

  /**
   * CasAutoAssignRoleSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   A config factory instance.
   */
  public function __construct(ConfigFactoryInterface $config) {
    $this->settings = $config->get('cas.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[CasHelper::EVENT_PRE_REGISTER][] = ['assignRolesOnRegistration'];
    return $events;
  }

  /**
   * The entry point for our subscriber.
   *
   * Assign roles to a user that just registered via CAS.
   *
   * @param \Drupal\cas\Event\CasPreRegisterEvent $event
   *   The event object.
   */
  public function assignRolesOnRegistration(CasPreRegisterEvent $event) {
    $auto_assigned_roles = $this->settings->get('user_accounts.auto_assigned_roles');
    if (!empty($auto_assigned_roles)) {
      $event->setPropertyValue('roles', $auto_assigned_roles);
    }
  }

}
