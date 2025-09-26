<?php

namespace Drupal\az_eds_user\EventSubscriber;

use Drupal\cas\Service\CasUserManager;
use Drupal\externalauth\AuthmapInterface;
use Drupal\ldap_user\Processor\DrupalUserProcessor;
use Drupal\cas\Event\CasPostValidateEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Repond to import of persons from the profiles API.
 */
class AzLdapCas implements EventSubscriberInterface {

  /**
   * @var \Drupal\cas\Service\CasUserManager
   */
  protected $casUserManager;

  /**
   * Externalauth.
   *
   * @var \Drupal\externalauth\AuthmapInterface
   */
  protected $externalAuth;

  /**
   * Drupal user processor.
   *
   * @var \Drupal\ldap_user\Processor\DrupalUserProcessor
   */
  protected $drupalUserProcessor;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an AZPersonProfilesImportEventSubscriber.
   *
   * @param \Drupal\cas\Service\CasUserManager $casUserManager
   *   The CAS user manager service.
   * @param \Drupal\externalauth\AuthmapInterface $authmap
   *   AuthmapInterface.
   * @param \Drupal\ldap_user\Processor\DrupalUserProcessor $processor
   *   Drupal user processor.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(CasUserManager $casUserManager, AuthmapInterface $authmap, DrupalUserProcessor $processor, EntityTypeManagerInterface $entityTypeManager) {
    $this->casUserManager = $casUserManager;
    $this->externalAuth = $authmap;
    $this->drupalUserProcessor = $processor;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Get subscribed events.
   *
   * @inheritdoc
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[CasPostValidateEvent::class] = ['onValidate'];
    return $events;
  }

  /**
   * Respond to events on CAS validation.
   *
   * @param \Drupal\cas\Event\CasPostValidateEvent $event
   *   The event object.
   */
  public function onValidate(CasPostValidateEvent $event) {
    // See who was validated.
    $bag = $event->getCasPropertyBag();
    $username = $bag->getUsername();
    // Check if a CAS user exists.
    if ($this->externalAuth->getUid($username, 'cas') === FALSE) {
      // User does not exist, attempt LDAP provisioning.
      $result = $this->drupalUserProcessor->createDrupalUserFromLdapEntry(
        [
          'name' => $username,
          'status' => TRUE,
        ]
      );
      // Successfully provisioned LDAP user for which there is no cas account.
      if ($result) {
        // Get the mapped uid of the new user.
        if ($uid = $this->externalAuth->getUid($username, 'ldap_user')) {
          $user = $this->entityTypeManager->getStorage('user')->load($uid);
          // We have the user that ldap_user provisioned, set the cas account.
          if (!empty($user)) {
            $this->casUserManager->setCasUsernameForAccount($user, $user->getAccountName());
          }
        }
      }
    }
  }

}
