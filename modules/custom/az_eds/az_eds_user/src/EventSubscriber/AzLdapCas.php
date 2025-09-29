<?php

namespace Drupal\az_eds_user\EventSubscriber;

use Drupal\cas\Service\CasUserManager;
use Drupal\externalauth\AuthmapInterface;
use Drupal\ldap_user\Processor\DrupalUserProcessor;
use Drupal\cas\Event\CasPostValidateEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ldap_query\Controller\QueryController;
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
   * Query controller.
   *
   * @var \Drupal\ldap_query\Controller\QueryController
   *   Controller helper to use for LDAP queries.
   */
  protected $ldapQuery;

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
   * @param \Drupal\ldap_query\Controller\QueryController $ldapQuery
   *   Controller helper to use for LDAP queries.
   */
  public function __construct(CasUserManager $casUserManager, AuthmapInterface $authmap, DrupalUserProcessor $processor, EntityTypeManagerInterface $entityTypeManager, QueryController $ldapQuery) {
    $this->casUserManager = $casUserManager;
    $this->externalAuth = $authmap;
    $this->drupalUserProcessor = $processor;
    $this->entityTypeManager = $entityTypeManager;
    $this->ldapQuery = $ldapQuery;
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
   * Check if an individual user is allowed by the ldap query.
   *
   * @param string $authname
   *   The username to check against the query.
   *
   * @return bool
   *   Whether or not the authname exists in the query.
   */
  protected function userAllowedByQuery(string $authname) {

    // @todo formalize this by looking up query config from server.
    $this->ldapQuery->load('az_eds_user');
    // Add a clause for our authname to the existing filter.
    $original_filter = $this->ldapQuery->getFilter();
    $filter = '(&' . $original_filter .
      '(uid=' .
      ldap_escape($authname, "", LDAP_ESCAPE_FILTER) .
      '))';
    $this->ldapQuery->execute($filter);
    $results = $this->ldapQuery->getRawResults();
    foreach ($results as $result) {
      // Make sure the result contains the user.
      $row = $result->getAttributes();
      $uid = $row['uid'] ?? [];
      $uid = reset($uid);
      if ($uid === $authname) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Respond to events on CAS validation for JIT provisioning.
   *
   * @param \Drupal\cas\Event\CasPostValidateEvent $event
   *   The event object.
   */
  public function onValidate(CasPostValidateEvent $event) {
    // See who was validated.
    $bag = $event->getCasPropertyBag();
    $username = $bag->getUsername();
    // Check if a CAS user exists.
    $cas_uid = $this->externalAuth->getUid($username, 'cas');
    if ($cas_uid === FALSE) {
      if (!$this->userAllowedByQuery($username)) {
        // Not allowed by query.
        return;
      }
      // User does not exist, but is allowed, attempt LDAP provisioning.
      $result = $this->drupalUserProcessor->createDrupalUserFromLdapEntry(
        [
          'name' => $username,
          'status' => TRUE,
        ]
      );
      // Successfully provisioned LDAP user for which there is no cas account.
      if ($result) {
        // Get the mapped uid of the new user.
        if ($ldap_uid = $this->externalAuth->getUid($username, 'ldap_user')) {
          $user = $this->entityTypeManager->getStorage('user')->load($ldap_uid);
          // We have the user that ldap_user provisioned, set the cas account.
          if (!empty($user)) {
            $this->casUserManager->setCasUsernameForAccount($user, $user->getAccountName());
          }
        }
      }
    }
    // CAS user existed but disallowed by query (e.g. user has lost membership.)
    elseif (!$this->userAllowedByQuery($username)) {
      if ($ldap_uid = $this->externalAuth->getUid($username, 'ldap_user')) {
        $user = $this->entityTypeManager->getStorage('user')->load($ldap_uid);
        // User originally provisioned by LDAP, therefore cancel the account.
        // The implication is to NOT cancel an account created by hand.
        // @todo is this workflow correct?
        if (!empty($user) && !$user->isBlocked()) {
          // Deactivate the account.
          $user->block();
          $user->save();
          // After this event, CAS login will fail because account blocked.
        }
      }
    }
    // CAS user existed, and is still allowed by query.
    else {
      $user = $this->entityTypeManager->getStorage('user')->load($cas_uid);
      // Check if user needs to be unblocked.
      if (!empty($user) && $user->isBlocked()) {
        // Deactivate the account.
        $user->activate();
        $user->save();
        // After this event, CAS will succeed because they are active.
      }
    }
  }

}
