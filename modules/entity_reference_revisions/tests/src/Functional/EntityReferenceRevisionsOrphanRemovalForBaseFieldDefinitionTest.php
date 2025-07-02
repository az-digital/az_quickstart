<?php

namespace Drupal\Tests\entity_reference_revisions\Functional;

use Drupal\entity_composite_relationship_test\Entity\EntityTestCompositeRelationship;
use Drupal\entity_host_relationship_test\Entity\EntityTestHostRelationship;

/**
 * Tests orphan composite revisions are properly removed.
 *
 * @group entity_reference_revisions
 */
class EntityReferenceRevisionsOrphanRemovalForBaseFieldDefinitionTest extends EntityReferenceRevisionsOrphanRemovalTest {

  /**
   * A user with administration access.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'field',
    'entity_reference_revisions',
    'entity_composite_relationship_test',
    'entity_host_relationship_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function insertRevisionableData() {
    /** @var \Drupal\node\NodeStorageInterface $entity_host_storage */
    $entity_host_storage = \Drupal::entityTypeManager()->getStorage('entity_host_relationship_test');
    // Scenario 1: A composite with a default revision that is referenced and an
    // old revision that is not. Result: Only the old revision is deleted.
    $composite_entity_first = EntityTestCompositeRelationship::create([
      'name' => 'first not used, second used',
      'parent_id' => 1000,
      'parent_type' => 'entity_host_relationship_test',
      'parent_field_name' => 'entity',
    ]);
    $composite_entity_first->save();
    $composite_entity_first = EntityTestCompositeRelationship::load($composite_entity_first->id());
    $composite_entity_first->setNewRevision(TRUE);
    $composite_entity_first->save();
    $entity_host = EntityTestHostRelationship::create([
      'name' => 'First composite',
      'entity' => $composite_entity_first,
    ]);
    $entity_host->save();

    // Scenario 2: A composite with an old revision that is used and a default
    // revision that is not. Result: Nothing should be deleted.
    $composite_entity_second = EntityTestCompositeRelationship::create([
      'name' => 'first used, second not used',
    ]);
    $composite_entity_second->save();
    $entity_host = EntityTestHostRelationship::create([
      'name' => 'Second composite',
      'entity' => $composite_entity_second,
    ]);
    $entity_host->save();
    $entity_host = $this->getEntityHostByName('Second composite');
    $entity_host = $entity_host_storage->createRevision($entity_host);
    $entity_host->set('entity', NULL);
    $entity_host->save();
    $composite_entity_second = EntityTestCompositeRelationship::load($composite_entity_second->id());
    $composite_entity_second->setNewRevision(TRUE);
    $composite_entity_second->save();

    // Scenario 3: A composite with an old revision and a default revision both
    // that are not used with empty parent fields. Result: Nothing should be
    // deleted since we do not know if it is still used.
    $composite_entity_third = EntityTestCompositeRelationship::create([
      'name' => 'first not used, second not used',
    ]);
    $composite_entity_third->save();
    $composite_entity_third = EntityTestCompositeRelationship::load($composite_entity_third->id());
    $composite_entity_third->setNewRevision(TRUE);
    $composite_entity_third->save();

    // Scenario 4: A composite with an old revision and a default revision both
    // that are not used with filled parent fields. Result: Should first delete
    // the old revision and then the default revision. Delete the entity too.
    $composite_entity_fourth = EntityTestCompositeRelationship::create([
      'name' => '1st filled not, 2nd filled not',
      'parent_id' => 1001,
      'parent_type' => 'entity_host_relationship_test',
      'parent_field_name' => 'entity',
    ]);
    $composite_entity_fourth->save();
    $composite_entity_fourth = EntityTestCompositeRelationship::load($composite_entity_fourth->id());
    $composite_entity_fourth->setNewRevision(TRUE);
    $composite_entity_fourth->set('parent_id', 1001);
    $composite_entity_fourth->save();

    // Scenario 5: A composite with many revisions and 2 at least used. Result:
    // Delete all unused revisions.
    $composite_entity_fifth = EntityTestCompositeRelationship::create([
      'name' => '1st not, 2nd used, 3rd not, 4th',
      'parent_id' => 1001,
      'parent_type' => 'entity_host_relationship_test',
      'parent_field_name' => 'entity',
    ]);
    $composite_entity_fifth->save();
    $composite_entity_fifth = EntityTestCompositeRelationship::load($composite_entity_fifth->id());
    $composite_entity_fifth->setNewRevision(TRUE);
    $composite_entity_fifth->save();
    $entity_host = EntityTestHostRelationship::create([
      'name' => 'Third composite',
      'entity' => $composite_entity_fifth,
    ]);
    $entity_host->save();
    $entity_host = $this->getEntityHostByName('Second composite');
    $entity_host = $entity_host_storage->createRevision($entity_host);
    $entity_host->set('entity', NULL);
    $entity_host->save();
    $composite_entity_fifth = EntityTestCompositeRelationship::load($composite_entity_fifth->id());
    $composite_entity_fifth->setNewRevision(TRUE);
    $composite_entity_fifth->save();
    $entity_host = $this->getEntityHostByName('Third composite');
    $entity_host = $entity_host_storage->createRevision($entity_host);
    $entity_host->set('entity', $composite_entity_fifth);
    $entity_host->save();

    // Scenario 6: A composite with wrong parent fields filled pointing to a non
    // existent parent (Parent 1). However, Parent 2 references it. Result: Must
    // not be deleted.
    $entity_host = EntityTestHostRelationship::create([
      'name' => 'DELETED composite',
    ]);
    $entity_host->save();
    $composite_entity_sixth = EntityTestCompositeRelationship::create([
      'name' => 'wrong parent fields',
      'parent_id' => $entity_host->id(),
      'parent_type' => 'entity_host_relationship_test',
      'parent_field_name' => 'entity',
    ]);
    $composite_entity_sixth->save();
    $entity_host->delete();
    $entity_host = EntityTestHostRelationship::create([
      'name' => 'Fourth composite',
      'entity' => $composite_entity_sixth,
    ]);
    $entity_host->save();
  }

  /**
   * {@inheritdoc}
   */
  public function insertNonRevisionableData() {
    // Scenario 1: A composite with a default revision that is referenced and an
    // old revision that is not. Result: Only the old revision is deleted.
    $composite_entity_first = EntityTestCompositeRelationship::create([
      'name' => 'NR first not used, second used',
      'parent_id' => 1001,
      'parent_type' => 'entity_host_relationship_test',
      'parent_field_name' => 'entity',
    ]);
    $composite_entity_first->save();
    $composite_entity_first = EntityTestCompositeRelationship::load($composite_entity_first->id());
    $composite_entity_first->setNewRevision(TRUE);
    $composite_entity_first->save();
    $entity_host = EntityTestHostRelationship::create([
      'name' => 'First NR composite',
      'entity' => $composite_entity_first,
    ]);
    $entity_host->save();

    // Scenario 2: A composite with an old revision that is used and a default
    // revision that is not. Result: Nothing should be deleted.
    $composite_entity_second = EntityTestCompositeRelationship::create([
      'name' => 'NR first used, second not used',
    ]);
    $composite_entity_second->save();
    $entity_host = EntityTestHostRelationship::create([
      'name' => 'Second NR composite',
      'entity' => $composite_entity_second,
    ]);
    $entity_host->save();
    $composite_entity_second = EntityTestCompositeRelationship::load($composite_entity_second->id());
    $composite_entity_second->setNewRevision(TRUE);
    $composite_entity_second->save();

    // Scenario 3: A composite with many revisions and 2 at least used. Result:
    // Delete all unused revisions.
    $composite_entity_third = EntityTestCompositeRelationship::create([
      'name' => 'NR 1st not, 2nd, 3rd not, 4th',
      'parent_id' => 1001,
      'parent_type' => 'entity_host_relationship_test',
      'parent_field_name' => 'entity',
    ]);
    $composite_entity_third->save();
    $composite_entity_third = EntityTestCompositeRelationship::load($composite_entity_third->id());
    $composite_entity_third->setNewRevision(TRUE);
    $composite_entity_third->save();
    $entity_host = EntityTestHostRelationship::create([
      'name' => 'Third NR composite',
      'entity' => $composite_entity_third,
    ]);
    $entity_host->save();
    $entity_host = $this->getEntityHostByName('Third NR composite');
    $entity_host->set('entity', NULL);
    $entity_host->save();
    $composite_entity_third = EntityTestCompositeRelationship::load($composite_entity_third->id());
    $composite_entity_third->setNewRevision(TRUE);
    $composite_entity_third->save();
    $entity_host = $this->getEntityHostByName('Third NR composite');
    $entity_host->set('entity', $composite_entity_third);
    $entity_host->save();
  }

  /**
   * Get an entity host from the database based on its name.
   *
   * @param string $name
   *   A entity name.
   * @param bool $reset
   *   (optional) Whether to reset the entity cache.
   *
   * @return \Drupal\Core\Entity\RevisionableInterface
   *   A revisionable entity matching $name.
   */
  protected function getEntityHostByName($name, $reset = FALSE) {
    if ($reset) {
      \Drupal::entityTypeManager()->getStorage('entity_host_relationship_test')->resetCache();
    }
    $name = (string) $name;
    /** @var \Drupal\Core\Entity\RevisionableInterface[] $entities */
    $entities = \Drupal::entityTypeManager()
      ->getStorage('entity_host_relationship_test')
      ->loadByProperties(['name' => $name]);
    // Load the first entity returned from the database.
    return reset($entities);
  }

}
