<?php

namespace Drupal\webform_access;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\EntityStorage\WebformEntityStorageTrait;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Storage controller class for "webform_access_group" configuration entities.
 */
class WebformAccessGroupStorage extends ConfigEntityStorage implements WebformAccessGroupStorageInterface {

  use WebformEntityStorageTrait;

  /**
   * Active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->database = $container->get('database');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultiple(array $ids = NULL) {
    /** @var \Drupal\webform_access\WebformAccessGroupInterface[] $webform_access_groups */
    $webform_access_groups = parent::doLoadMultiple($ids);

    // Load admin.
    $result = $this->database->select('webform_access_group_admin', 'gu')
      ->fields('gu', ['group_id', 'uid'])
      ->condition('group_id', $ids, 'IN')
      ->orderBy('group_id')
      ->orderBy('uid')
      ->execute();
    $admins = [];
    while ($record = $result->fetchAssoc()) {
      $admins[$record['group_id']][] = $record['uid'];
    }
    foreach ($webform_access_groups as $group_id => $webform_access_group) {
      $webform_access_group->setAdminIds($admins[$group_id] ?? []);
    }

    // Load users.
    $result = $this->database->select('webform_access_group_user', 'gu')
      ->fields('gu', ['group_id', 'uid'])
      ->condition('group_id', $ids, 'IN')
      ->orderBy('group_id')
      ->orderBy('uid')
      ->execute();
    $users = [];
    while ($record = $result->fetchAssoc()) {
      $users[$record['group_id']][] = $record['uid'];
    }
    foreach ($webform_access_groups as $group_id => $webform_access_group) {
      $webform_access_group->setUserIds($users[$group_id] ?? []);
    }

    // Load entities.
    $result = $this->database->select('webform_access_group_entity', 'ge')
      ->fields('ge', ['group_id', 'entity_type', 'entity_id', 'field_name', 'webform_id'])
      ->condition('group_id', $ids, 'IN')
      ->orderBy('group_id')
      ->execute();
    $entities = [];
    while ($record = $result->fetchAssoc()) {
      $group_id = $record['group_id'];
      unset($record['group_id']);
      $entities[$group_id][] = implode(':', $record);
    }
    foreach ($webform_access_groups as $group_id => $webform_access_group) {
      $webform_access_group->setEntityIds($entities[$group_id] ?? []);
    }

    return $webform_access_groups;
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave($id, EntityInterface $entity) {
    /** @var \Drupal\webform_access\WebformAccessGroupInterface $entity */
    $result = parent::doSave($id, $entity);

    // Save admins.
    $admins = $entity->getAdminIds();
    $this->database->delete('webform_access_group_admin')
      ->condition('group_id', $entity->id())
      ->execute();
    $query = $this->database
      ->insert('webform_access_group_admin')
      ->fields(['group_id', 'uid']);
    $values = ['group_id' => $entity->id()];
    foreach ($admins as $uid) {
      $values['uid'] = $uid;
      $query->values($values);
    }
    $query->execute();

    // Save users.
    $users = $entity->getUserIds();
    $this->database->delete('webform_access_group_user')
      ->condition('group_id', $entity->id())
      ->execute();
    $query = $this->database
      ->insert('webform_access_group_user')
      ->fields(['group_id', 'uid']);
    $values = ['group_id' => $entity->id()];
    foreach ($users as $uid) {
      $values['uid'] = $uid;
      $query->values($values);
    }
    $query->execute();

    // Save entities.
    $entities = $entity->getEntityIds();
    $this->database->delete('webform_access_group_entity')
      ->condition('group_id', $entity->id())
      ->execute();
    $query = $this->database
      ->insert('webform_access_group_entity')
      ->fields(['group_id', 'entity_type', 'entity_id', 'field_name', 'webform_id']);
    $values = ['group_id' => $entity->id()];
    foreach ($entities as $entity) {
      [$values['entity_type'], $values['entity_id'], $values['field_name'], $values['webform_id']] = explode(':', $entity);
      $query->values($values);
    }
    $query->execute();

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $entities) {
    /** @var \Drupal\webform_access\WebformAccessGroupInterface[] $entities */
    foreach ($entities as $entity) {
      $this->database->delete('webform_access_group_admin')
        ->condition('group_id', $entity->id())
        ->execute();
      $this->database->delete('webform_access_group_user')
        ->condition('group_id', $entity->id())
        ->execute();
      $this->database->delete('webform_access_group_entity')
        ->condition('group_id', $entity->id())
        ->execute();
    }
    return parent::delete($entities);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByEntities(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, $type = NULL) {
    $query = $this->database->select('webform_access_group_entity', 'ge');
    $query->fields('ge', ['group_id']);

    // Webform.
    if ($webform) {
      $query->condition('webform_id', $webform->id());
    }
    // Source entity.
    if ($source_entity) {
      $query->condition('entity_type', $source_entity->getEntityTypeId());
      $query->condition('entity_id', $source_entity->id());
    }
    // Account.
    if ($account) {
      $query->innerjoin('webform_access_group_user', 'gu', 'ge.group_id = gu.group_id');
      $query->condition('uid', $account->id());
    }
    // Webform access type.
    if ($type) {
      $type_group_ids = $this->getQuery()
        ->condition('type', $type)
        ->accessCheck(FALSE)
        ->execute();
      if (empty($type_group_ids)) {
        return [];
      }
      $query->condition('group_id', $type_group_ids, 'IN');
    }

    $group_ids = $query->execute()->fetchCol();
    return $group_ids ? $this->loadMultiple($group_ids) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getUserEntities(AccountInterface $account, $entity_type = NULL) {
    /** @var \Drupal\webform_access\WebformAccessGroupInterface[] $webform_access_groups */
    $webform_access_groups = $this->loadByEntities(NULL, NULL, $account);

    $source_entity_ids = [];
    foreach ($webform_access_groups as $webform_access_group) {
      $entities = $webform_access_group->getEntityIds();
      foreach ($entities as $entity) {
        [$source_entity_type, $source_entity_id] = explode(':', $entity);
        if (!$entity_type || $source_entity_type === $entity_type) {
          $source_entity_ids[] = $source_entity_id;
        }
      }
    }
    return $this->getEntityStorage($entity_type)->loadMultiple($source_entity_ids);
  }

}
