<?php

namespace Drupal\workbench_access_circular_test;

use Drupal\content_moderation\StateTransitionValidationInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\workbench_access\UserSectionStorageInterface;
use Drupal\workflows\StateInterface;
use Drupal\workflows\WorkflowInterface;

/**
 * Defines a class for decorating state transition validation.
 */
class StateTransitionValidation implements StateTransitionValidationInterface {

  /**
   * Original service.
   *
   * @var \Drupal\content_moderation\StateTransitionValidationInterface
   */
  protected $original;

  /**
   * User section storage.
   *
   * @var \Drupal\workbench_access\UserSectionStorageInterface
   */
  protected $userSectionStorage;

  /**
   * Constructs a new StateTransitionValidation.
   *
   * @param \Drupal\content_moderation\StateTransitionValidationInterface $original
   *   Decorated state validation service.
   * @param \Drupal\workbench_access\UserSectionStorageInterface $userSectionStorage
   *   The user section storage service.
   */
  public function __construct(StateTransitionValidationInterface $original, UserSectionStorageInterface $userSectionStorage) {
    $this->original = $original;
    $this->userSectionStorage = $userSectionStorage;
  }

  /**
   * {@inheritdoc}
   */
  public function getValidTransitions(ContentEntityInterface $entity, AccountInterface $user) {
    return $this->original->getValidTransitions($entity, $user);
  }

  /**
   * {@inheritdoc}
   */
  public function isTransitionValid(WorkflowInterface $workflow, StateInterface $original_state, StateInterface $new_state, AccountInterface $user, ContentEntityInterface $entity) {
    return $this->original->isTransitionValid($workflow, $original_state, $new_state, $user, $entity);
  }

}
