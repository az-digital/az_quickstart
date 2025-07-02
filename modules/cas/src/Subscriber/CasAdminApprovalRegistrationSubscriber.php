<?php

namespace Drupal\cas\Subscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\externalauth\Event\ExternalAuthEvents;
use Drupal\externalauth\Event\ExternalAuthRegisterEvent;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Acts when the new account creation is subject of admin approval.
 *
 * This subscriber provides tha standard Drupal emails and status message when
 * a new account is registered but the site requires admin approval. Third party
 * may override this behavior by providing a subscriber with a higher priority,
 * implementing their logic, and stopping the event propagation.
 */
class CasAdminApprovalRegistrationSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new event subscriber service instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, MessengerInterface $messenger) {
    $this->configFactory = $configFactory;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      ExternalAuthEvents::REGISTER => 'onBlockedAccountRegistration',
    ];
  }

  /**
   * Acts just after a new blocked account is registered and provides a message.
   *
   * @param \Drupal\externalauth\Event\ExternalAuthRegisterEvent $event
   *   The ExternalAuth register event.
   */
  public function onBlockedAccountRegistration(ExternalAuthRegisterEvent $event): void {
    if ($event->getProvider() !== 'cas') {
      return;
    }

    $account = $event->getAccount();
    if ($account->isBlocked()) {
      if ($this->configFactory->get('cas.settings')->get('user_accounts.auto_register_follow_registration_policy')) {
        $user_settings = $this->configFactory->get('user.settings');
        if ($user_settings->get('register') === UserInterface::REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL) {
          // Provide the standard Drupal emails and status message.
          _user_mail_notify('register_pending_approval', $account);
          $this->messenger->addStatus($this->t('Thank you for applying for an account. Your account is currently pending approval by the site administrator.<br />In the meantime, a welcome message with further instructions has been sent to your email address.'));
        }
      }
    }
  }

}
