<?php

namespace Drupal\az_eds_user\EventSubscriber;

use Drupal\cas\Event\CasPostValidateEvent;
use Drupal\cas\Service\CasUserManager;
use Drupal\externalauth\AuthmapInterface;
use Drupal\ldap_query\Controller\QueryController;
use Drupal\ldap_user\Processor\DrupalUserProcessor;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Respond to successful CAS ticket validation.
 */
class AZLdapCas implements EventSubscriberInterface {

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
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs an AZLdapCas.
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
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel service.
   */
  public function __construct(CasUserManager $casUserManager, AuthmapInterface $authmap, DrupalUserProcessor $processor, EntityTypeManagerInterface $entityTypeManager, QueryController $ldapQuery, LoggerChannelInterface $logger) {
    $this->casUserManager = $casUserManager;
    $this->externalAuth = $authmap;
    $this->drupalUserProcessor = $processor;
    $this->entityTypeManager = $entityTypeManager;
    $this->ldapQuery = $ldapQuery;
    $this->logger = $logger;
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
   * Update a user's roles.
   *
   * @param \Drupal\user\UserInterface $user
   *   The username to check via query.
   * @param array $roles
   *   Array of role machine names.
   */
  protected function synchronizeRoles(UserInterface $user, array $roles) {
    // User's original roles.
    $original_roles = $user->getRoles();
    // We do not attempt to manage these roles.
    $unmanaged_roles = [
      RoleInterface::AUTHENTICATED_ID,
      RoleInterface::ANONYMOUS_ID,
    ];
    // List of roles to remove.
    $remove = array_diff($original_roles, $roles, $unmanaged_roles);
    // List of roles to add.
    $add = array_diff($roles, $original_roles, $unmanaged_roles);
    // Update the user roles.
    foreach ($remove as $role) {
      try {
        $user->removeRole($role);
        $this->logger->notice('Removing role @role from @user during LDAP mapping.',
        [
          '@role' => $role,
          '@user' => $user->getDisplayName(),
        ]);
      }
      catch (\InvalidArgumentException $e) {
        // Some roles are not valid to assign.
      }
    }
    foreach ($add as $role) {
      try {
        $user->addRole($role);
        $this->logger->notice('Adding role @role to @user during LDAP mapping.',
        [
          '@role' => $role,
          '@user' => $user->getDisplayName(),
        ]);
      }
      catch (\InvalidArgumentException $e) {
        // Some roles are not valid to assign.
      }
    }
    // Save the user if necessary.
    if (!empty($add) && !empty($remove)) {
      $user->save();
    }
  }

  /**
   * Check if an individual user is granted roles via LDAP.
   *
   * @param string $authname
   *   The username to check via query.
   *
   * @return array
   *   Machine name of allowed roles.
   */
  protected function userAllowedRoles(string $authname) {

    $roles = [];
    $storage = $this->entityTypeManager->getStorage('az_ldap_role');
    $ids = $storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->execute();
    $mappings = $storage->loadMultiple($ids);
    /** @var \Drupal\az_eds_user\AZLdapRoleInterface $mapping */
    foreach ($mappings as $mapping) {
      $query = $mapping->get('query');
      $role = $mapping->get('role');
      $this->ldapQuery->load($query);
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
          $roles[] = $role;
        }
      }
    }

    return array_unique($roles);
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
    $ldap_uid = $this->externalAuth->getUid($username, 'ldap_user');
    if (($cas_uid !== FALSE) && ($ldap_uid === FALSE)) {
      // Regular CAS user. We don't have responsibility for this user.
      return;
    }
    $roles = $this->userAllowedRoles($username);
    if ($cas_uid === FALSE) {
      if (empty($roles)) {
        // No roles allowed for this user. Nothing to do.
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
            $this->logger->notice('Provisoning user @user during LDAP mapping.',
            [
              '@user' => $user->getDisplayName(),
            ]);
            $this->casUserManager->setCasUsernameForAccount($user, $user->getAccountName());
            $this->synchronizeRoles($user, $roles);
          }
        }
      }
    }
    // CAS user existed but disallowed by query (e.g. user has lost membership.)
    elseif (empty($roles)) {
      if ($ldap_uid !== FALSE) {
        $user = $this->entityTypeManager->getStorage('user')->load($ldap_uid);
        // User originally provisioned by LDAP, therefore cancel the account.
        // The implication is to NOT cancel an account created by hand.
        // @todo is this workflow correct?
        if (!empty($user) && !$user->isBlocked()) {
          // Deactivate the account.
          $user->block();
          $user->save();
          $this->logger->notice('Blocking user @user during LDAP mapping.',
          [
            '@user' => $user->getDisplayName(),
          ]);
          // After this event, CAS login will fail because account blocked.
        }
      }
    }
    // CAS user existed, and is still allowed by query.
    else {
      $user = $this->entityTypeManager->getStorage('user')->load($cas_uid);
      $this->synchronizeRoles($user, $roles);
      // Check if user needs to be unblocked.
      if (!empty($user) && $user->isBlocked()) {
        // Reactivate the account.
        $user->activate();
        $user->save();
        $this->logger->notice('Reactivating user @user during LDAP mapping.',
        [
          '@user' => $user->getDisplayName(),
        ]);
        // After this event, CAS will succeed because they are active.
      }
    }
  }

}
