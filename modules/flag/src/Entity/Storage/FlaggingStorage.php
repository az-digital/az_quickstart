<?php

namespace Drupal\flag\Entity\Storage;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;

/**
 * Default SQL flagging storage.
 */
class FlaggingStorage extends SqlContentEntityStorage implements FlaggingStorageInterface {

  /**
   * Stores loaded flags per user, entity type and IDs.
   *
   * @var array
   */
  protected $flagIdsByEntity = [];

  /**
   * Stores global flags per entity type and IDs.
   *
   * @var array
   */
  protected $globalFlagIdsByEntity = [];

  /**
   * {@inheritdoc}
   */
  public function resetCache(?array $ids = NULL) {
    parent::resetCache($ids);
    $this->flagIdsByEntity = [];
    $this->globalFlagIdsByEntity = [];
  }

  /**
   * {@inheritdoc}
   */
  public function loadIsFlagged(EntityInterface $entity, AccountInterface $account, $session_id = NULL) {
    if ($account->isAnonymous() && is_null($session_id)) {
      throw new \LogicException('Anonymous users must be identified by session_id');
    }

    $flag_ids = $this->loadIsFlaggedMultiple([$entity], $account, $session_id);
    return $flag_ids[$entity->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function loadIsFlaggedMultiple(array $entities, AccountInterface $account, $session_id = NULL) {
    if ($account->isAnonymous() && is_null($session_id)) {
      throw new \LogicException('Anonymous users must be identified by session_id');
    }

    // Set a dummy value for $session_id for an authenticated user so that we
    // can use it as a key in the cache array.
    if (!$account->isAnonymous()) {
      $session_id = 0;
    }

    $flag_ids_by_entity = [];

    if (!$entities) {
      return $flag_ids_by_entity;
    }

    // All entities must be of the same type, get the entity type from the
    // first.
    $entity_type_id = reset($entities)->getEntityTypeId();
    $ids_to_load = [];

    // Loop over all requested entities, if they are already in the loaded list,
    // get then from there, merge the global and per-user flags together.
    foreach ($entities as $entity) {
      if (isset($this->flagIdsByEntity[$account->id()][$session_id][$entity_type_id][$entity->id()])) {
        $flag_ids_by_entity[$entity->id()] = array_merge($this->flagIdsByEntity[$account->id()][$session_id][$entity_type_id][$entity->id()], $this->globalFlagIdsByEntity[$entity_type_id][$entity->id()]);
      }
      else {
        $ids_to_load[$entity->id()] = [];
      }
    }

    // If there are no entities that need to be loaded, return the list.
    if (!$ids_to_load) {
      return $flag_ids_by_entity;
    }

    // Initialize the loaded lists with the missing ID's as an empty array.
    if (!isset($this->flagIdsByEntity[$account->id()][$session_id][$entity_type_id])) {
      $this->flagIdsByEntity[$account->id()][$session_id][$entity_type_id] = [];
    }
    if (!isset($this->globalFlagIdsByEntity[$entity_type_id])) {
      $this->globalFlagIdsByEntity[$entity_type_id] = [];
    }
    $this->flagIdsByEntity[$account->id()][$session_id][$entity_type_id] += $ids_to_load;
    $this->globalFlagIdsByEntity[$entity_type_id] += $ids_to_load;
    $flag_ids_by_entity += $ids_to_load;

    // Directly query the table to avoid the overhead of loading the content
    // entities.
    $query = $this->database->select('flagging', 'f')
      ->fields('f', ['entity_id', 'flag_id', 'global'])
      ->condition('entity_type', $entity_type_id)
      ->condition('entity_id', array_keys($ids_to_load), 'IN');

    // The flagging must either match the user or be global.
    $user_or_global_condition = $query->orConditionGroup()
      ->condition('global', 1);
    if ($account->isAnonymous()) {
      $uid_and_session_condition = $query->andConditionGroup()
        ->condition('uid', $account->id())
        ->condition('session_id', $session_id);
      $user_or_global_condition->condition($uid_and_session_condition);
    }
    else {
      $user_or_global_condition->condition('uid', $account->id());
    }

    $result = $query
      ->condition($user_or_global_condition)
      ->execute();

    // Loop over all results, put them in the cached list and the list that will
    // be returned.
    foreach ($result as $row) {
      if ($row->global) {
        $this->globalFlagIdsByEntity[$entity_type_id][$row->entity_id][$row->flag_id] = $row->flag_id;
      }
      else {
        $this->flagIdsByEntity[$account->id()][$session_id][$entity_type_id][$row->entity_id][$row->flag_id] = $row->flag_id;
      }
      $flag_ids_by_entity[$row->entity_id][$row->flag_id] = $row->flag_id;
    }

    return $flag_ids_by_entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function doPostSave(EntityInterface $entity, $update) {

    parent::doPostSave($entity, $update);

    // After updating or creating a flagging, add it to the cached flagging by
    // entity if already in static cache.
    /** @var \Drupal\flag\Entity\Flagging $entity */
    if ($entity->get('global')->value) {
      // If the global flags by entity for this entity have already been cached,
      // then add the newly created flagging.
      if (isset($this->globalFlagIdsByEntity[$entity->get('entity_type')->value][$entity->get('entity_id')->value])) {
        $this->globalFlagIdsByEntity[$entity->get('entity_type')->value][$entity->get('entity_id')->value][$entity->get('flag_id')->value] = $entity->get('flag_id')->value;
      }
    }
    else {
      // If the flags by entity for this entity/user have already been cached,
      // then add the newly created flagging.
      if (isset($this->flagIdsByEntity[$entity->get('uid')->target_id][$entity->get('entity_type')->value][$entity->get('entity_id')->value])) {
        $this->flagIdsByEntity[$entity->get('uid')->target_id][$entity->get('entity_type')->value][$entity->get('entity_id')->value][$entity->get('flag_id')->value] = $entity->get('flag_id')->value;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doDelete($entities) {

    parent::doDelete($entities);

    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $entities */
    foreach ($entities as $entity) {
      // After deleting a flagging, remove it from the cached flagging by entity
      // if already in static cache.
      if ($entity->get('global')->value) {
        if (isset($this->globalFlagIdsByEntity[$entity->get('entity_type')->value][$entity->get('entity_id')->value][$entity->get('flag_id')->value])) {
          unset($this->globalFlagIdsByEntity[$entity->get('entity_type')->value][$entity->get('entity_id')->value][$entity->get('flag_id')->value]);
        }
      }
      else {
        if (isset($this->flagIdsByEntity[$entity->get('uid')->target_id][$entity->get('entity_type')->value][$entity->get('entity_id')->value][$entity->get('flag_id')->value])) {
          unset($this->flagIdsByEntity[$entity->get('uid')->target_id][$entity->get('entity_type')->value][$entity->get('entity_id')->value][$entity->get('flag_id')->value]);
        }
      }
    }
  }

}
