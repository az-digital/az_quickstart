<?php

namespace Drupal\cas_test;

use Drupal\cas\Event\CasPreLoginEvent;
use Drupal\cas\Event\CasPreRegisterEvent;
use Drupal\cas\Event\CasPreValidateEvent;
use Drupal\cas\Service\CasHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to pre-login and pre-register events.
 */
class CasTestSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      CasHelper::EVENT_PRE_REGISTER => 'onPreRegister',
      CasHelper::EVENT_PRE_LOGIN => 'onPreLogin',
      CasHelper::EVENT_PRE_VALIDATE => 'onPreValidate',
    ];
  }

  /**
   * Change the username of the user being registered.
   *
   * @param \Drupal\cas\Event\CasPreRegisterEvent $event
   *   The event.
   */
  public function onPreRegister(CasPreRegisterEvent $event): void {
    // Add a prefix of "testing_" to the CAS username.
    $username = $event->getDrupalUsername();
    $new_username = 'testing_' . $username;
    $event->setDrupalUsername($new_username);

    $flag = \Drupal::state()->get('cas_test.flag');
    if ($flag === 'cancel register without message') {
      $event->cancelAutomaticRegistration();
    }
    elseif ($flag === 'cancel register with message') {
      $event->cancelAutomaticRegistration('Cancelled with a custom message.');
    }

    $blocked = \Drupal::state()->get('cas_test.blocked_status');
    if ($blocked) {
      $event->setPropertyValue('status', 0);
    }
  }

  /**
   * Cancels the login.
   *
   * @param \Drupal\cas\Event\CasPreLoginEvent $event
   *   The event.
   */
  public function onPreLogin(CasPreLoginEvent $event): void {
    $flag = \Drupal::state()->get('cas_test.flag');
    if ($flag === 'cancel login without message') {
      $event->cancelLogin();
    }
    elseif ($flag === 'cancel login with message') {
      $event->cancelLogin('Cancelled with a custom message.');
    }
  }

  /**
   * Triggers ticket validation failure.
   *
   * @param \Drupal\cas\Event\CasPreValidateEvent $event
   *   The pre-validate event.
   *
   * @see \Drupal\Tests\cas\Functional\CasPostLoginDestinationTest::testCachedRedirect()
   */
  public function onPreValidate(CasPreValidateEvent $event): void {
    if (\Drupal::state()->get('cas_test.enable_ticket_validation_failure')) {
      $event->setValidationPath('invalid/ticket/validation/path');
    }
  }

}
