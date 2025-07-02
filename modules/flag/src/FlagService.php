<?php

namespace Drupal\flag;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Flag service.
 *
 *  - Handles search requests for flags and flaggings.
 *  - Performs flagging and unflagging operations.
 */
class FlagService implements FlagServiceInterface {

  /**
   * The current user injected into the service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The anonymous session ID.
   *
   * @var string|null
   */
  protected $anonymousSessionId;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack|null $request_stack
   *   Te request stack.
   */
  public function __construct(
    AccountInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
    ?RequestStack $request_stack = NULL,
  ) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllFlags($entity_type = NULL, $bundle = NULL) {
    $query = $this->entityTypeManager->getStorage('flag')->getQuery();
    $query->accessCheck();
    if ($entity_type != NULL) {
      $query->condition('entity_type', $entity_type);
    }

    $ids = $query->execute();
    $flags = $this->getFlagsByIds($ids);

    if (isset($bundle)) {
      $flags = array_filter($flags, function (FlagInterface $flag) use ($bundle) {
        $bundles = $flag->getApplicableBundles();
        return in_array($bundle, $bundles);
      });
    }

    return $flags;
  }

  /**
   * {@inheritdoc}
   */
  public function getFlagging(FlagInterface $flag, EntityInterface $entity, ?AccountInterface $account = NULL, $session_id = NULL) {
    $this->populateFlaggerDefaults($account, $session_id);

    $flaggings = $this->getEntityFlaggings($flag, $entity, $account, $session_id);

    return !empty($flaggings) ? reset($flaggings) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAnonymousSessionId() {
    if (!$this->currentUser->isAnonymous()) {
      return NULL;
    }

    if ($this->anonymousSessionId !== NULL) {
      return $this->anonymousSessionId;
    }

    $request = $this->requestStack->getCurrentRequest();
    $session_id = $request?->getSession()->isStarted()
      ? $request?->getSession()->get('flag.session_id')
      : NULL;
    if (empty($session_id)) {
      $session_id = Crypt::randomBytesBase64();
    }

    $this->anonymousSessionId = $session_id;

    return $this->anonymousSessionId;
  }

  /**
   * Makes sure session is started.
   *
   * @see \Drupal\Core\TempStore\PrivateTempStore::startSession()
   */
  protected function ensureSession() {
    if (!$this->currentUser->isAnonymous()) {
      return;
    }

    $request = $this->requestStack->getCurrentRequest();
    $session = $request?->getSession();
    if (!$session->has('flag.session_id')) {
      $session->set('flag.session_id', $this->getAnonymousSessionId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function populateFlaggerDefaults(?AccountInterface &$account = NULL, &$session_id = NULL) {
    // Note that the $account parameter must be explicitly set to be passed by
    // reference for the case when the variable is NULL rather than an object;
    // also, it must be optional to allow a variable that is NULL to pass the
    // type-hint check.
    if (!isset($account)) {
      // If there isn't an account, set it to the current user.
      $account = $this->currentUser;
      // If the user is anonymous, get the session ID. Note that this does not
      // always mean that the session is started. Session is started explicitly
      // from FlagService->ensureSession() method.
      if (!isset($session_id) && $account->isAnonymous()) {
        $session_id = $this->getAnonymousSessionId();
      }
    }
    elseif ($account->isAnonymous() && $session_id === NULL) {
      throw new \LogicException('Anonymous users must be identified by session_id');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFlaggings(FlagInterface $flag, EntityInterface $entity, ?AccountInterface $account = NULL, $session_id = NULL) {
    $query = $this->entityTypeManager->getStorage('flagging')->getQuery();
    $query->accessCheck();
    $query->condition('flag_id', $flag->id());

    if (!is_null($account)) {
      if (!$flag->isGlobal()) {
        $query->condition('uid', $account->id());

        // Add the session ID to the query if $account is the anonymous user
        // (and require the $session_id parameter in this case).
        if ($account->isAnonymous()) {
          if (empty($session_id)) {
            throw new \LogicException('An anonymous user must be identified by session ID.');
          }

          $query->condition('session_id', $session_id);
        }
      }
    }

    $query->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id());

    $ids = $query->execute();

    return $this->getFlaggingsByIds($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getAllEntityFlaggings(EntityInterface $entity, ?AccountInterface $account = NULL, $session_id = NULL) {
    $query = $this->entityTypeManager->getStorage('flagging')->getQuery();
    $query->accessCheck();
    if (!empty($account)) {
      // Use an OR condition group to check that either the account flagged
      // the entity, or the flag itself is a global flag.
      $global_or_user = $query->orConditionGroup()
        ->condition('global', 1)
        ->condition('uid', $account->id());
      $query->condition($global_or_user);
      if ($account->isAnonymous()) {
        if (empty($session_id)) {
          throw new \LogicException('An anonymous user must be identified by session ID.');
        }

        $query->condition('session_id', $session_id);
      }
    }

    $query->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id());

    $ids = $query->execute();

    return $this->getFlaggingsByIds($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getFlagById($flag_id) {
    return $this->entityTypeManager->getStorage('flag')->load($flag_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getFlaggableById(FlagInterface $flag, $entity_id) {
    return $this->entityTypeManager->getStorage($flag->getFlaggableEntityTypeId())
      ->load($entity_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getFlaggingUsers(EntityInterface $entity, ?FlagInterface $flag = NULL) {
    $query = $this->entityTypeManager->getStorage('flagging')->getQuery();
    $query->accessCheck()
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id());

    if (!empty($flag)) {
      $query->condition('flag_id', $flag->id());
    }

    $ids = $query->accessCheck(FALSE)->execute();
    // Load the flaggings.
    $flaggings = $this->getFlaggingsByIds($ids);

    $user_ids = [];
    foreach ($flaggings as $flagging) {
      $user_ids[] = $flagging->get('uid')->first()->getValue()['target_id'];
    }

    return $this->entityTypeManager->getStorage('user')
      ->loadMultiple($user_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function flag(FlagInterface $flag, EntityInterface $entity, ?AccountInterface $account = NULL, $session_id = NULL) {
    $bundles = $flag->getBundles();

    $this->ensureSession();
    $this->populateFlaggerDefaults($account, $session_id);

    // Check the entity type corresponds to the flag type.
    if ($flag->getFlaggableEntityTypeId() != $entity->getEntityTypeId()) {
      throw new \LogicException('The flag does not apply to entities of this type.');
    }

    // Check the bundle is allowed by the flag.
    if (!empty($bundles) && !in_array($entity->bundle(), $bundles)) {
      throw new \LogicException('The flag does not apply to the bundle of the entity.');
    }

    // Check whether there is an existing flagging for the combination of flag,
    // entity, and user.
    if ($flag->isFlagged($entity, $account, $session_id)) {
      throw new \LogicException('The user has already flagged the entity with the flag.');
    }

    $flagging = $this->entityTypeManager->getStorage('flagging')->create([
      'uid' => $account->id(),
      'session_id' => $session_id,
      'flag_id' => $flag->id(),
      'entity_id' => $entity->id(),
      'entity_type' => $entity->getEntityTypeId(),
      'global' => $flag->isGlobal(),
    ]);

    $flagging->save();

    return $flagging;
  }

  /**
   * {@inheritdoc}
   */
  public function unflag(FlagInterface $flag, EntityInterface $entity, ?AccountInterface $account = NULL, $session_id = NULL) {
    $bundles = $flag->getBundles();

    $this->populateFlaggerDefaults($account, $session_id);

    // Check the entity type corresponds to the flag type.
    if ($flag->getFlaggableEntityTypeId() != $entity->getEntityTypeId()) {
      throw new \LogicException('The flag does not apply to entities of this type.');
    }

    // Check the bundle is allowed by the flag.
    if (!empty($bundles) && !in_array($entity->bundle(), $bundles)) {
      throw new \LogicException('The flag does not apply to the bundle of the entity.');
    }

    $flagging = $this->getFlagging($flag, $entity, $account, $session_id);

    // Check whether there is an existing flagging for the combination of flag,
    // entity, and user.
    if (!$flagging) {
      throw new \LogicException('The entity is not flagged by the user.');
    }

    $flagging->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function unflagAllByFlag(FlagInterface $flag) {
    $query = $this->entityTypeManager->getStorage('flagging')->getQuery();
    $query->accessCheck();
    $query->condition('flag_id', $flag->id());

    $ids = $query->execute();

    $flaggings = $this->getFlaggingsByIds($ids);

    $this->entityTypeManager->getStorage('flagging')->delete($flaggings);
  }

  /**
   * {@inheritdoc}
   */
  public function unflagAllByEntity(EntityInterface $entity) {
    $query = $this->entityTypeManager->getStorage('flagging')->getQuery();

    $query->accessCheck()
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id());

    $ids = $query->accessCheck(FALSE)->execute();

    $flaggings = $this->getFlaggingsByIds($ids);

    $this->entityTypeManager->getStorage('flagging')->delete($flaggings);
  }

  /**
   * {@inheritdoc}
   */
  public function unflagAllByUser(AccountInterface $account, $session_id = NULL) {
    $query = $this->entityTypeManager->getStorage('flagging')->getQuery();
    $query->accessCheck();
    $query->condition('uid', $account->id());

    if ($account->isAnonymous()) {
      if (empty($session_id)) {
        throw new \LogicException('An anonymous user must be identified by session ID.');
      }

      $query->condition('session_id', $session_id);
    }

    $ids = $query->execute();

    $flaggings = $this->getFlaggingsByIds($ids);

    $this->entityTypeManager->getStorage('flagging')->delete($flaggings);
  }

  /**
   * {@inheritdoc}
   */
  public function userFlagRemoval(UserInterface $account) {
    // Remove flags by this user.
    $this->unflagAllByUser($account);

    // Remove flags that have been done to this user.
    $this->unflagAllByEntity($account);
  }

  /**
   * Loads flag entities given their IDs.
   *
   * @param int[] $ids
   *   The flag IDs.
   *
   * @return \Drupal\flag\FlagInterface[]
   *   An array of flags.
   */
  protected function getFlagsByIds(array $ids) {
    return $this->entityTypeManager->getStorage('flag')->loadMultiple($ids);
  }

  /**
   * Loads flagging entities given their IDs.
   *
   * @param int[] $ids
   *   The flagging IDs.
   *
   * @return \Drupal\flag\FlaggingInterface[]
   *   An array of flaggings.
   */
  protected function getFlaggingsByIds(array $ids) {
    return $this->entityTypeManager->getStorage('flagging')->loadMultiple($ids);
  }

}
