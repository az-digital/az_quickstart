<?php

namespace Drupal\Tests\entity_reference_revisions\Kernel;

use Drupal\entity_composite_relationship_test\Entity\EntityTestCompositeRelationship;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests the entity_reference_revisions composite relationship.
 *
 * @group entity_reference_revisions
 */
class EntityReferenceRevisionsCompositeTest extends EntityKernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = array(
    'node',
    'field',
    'entity_reference_revisions',
    'entity_composite_relationship_test',
    'language'
  );

  /**
   * The current database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   *
   */
  protected $entityTypeManager;

  /**
   * The cron service.
   *
   * @var \Drupal\Core\Cron
   */
  protected $cron;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test_composite');
    $this->installSchema('node', ['node_access']);

    // Create article content type.
    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();

    // Create the reference to the composite entity test.
    $field_storage = FieldStorageConfig::create(array(
      'field_name' => 'composite_reference',
      'entity_type' => 'node',
      'type' => 'entity_reference_revisions',
      'settings' => array(
        'target_type' => 'entity_test_composite'
      ),
    ));
    $field_storage->save();
    $field = FieldConfig::create(array(
      'field_storage' => $field_storage,
      'bundle' => 'article',
      'translatable' => FALSE,
    ));
    $field->save();

    // Inject database connection, entity type manager and cron for the tests.
    $this->database = \Drupal::database();
    $this->entityTypeManager = \Drupal::entityTypeManager();
    $this->cron = \Drupal::service('cron');
  }

  /**
   * Test for maintaining composite relationship.
   *
   * Tests that the referenced entity saves the parent type and id when saving.
   */
  public function testEntityReferenceRevisionsCompositeRelationship() {
    // Create the test composite entity.
    $composite = EntityTestCompositeRelationship::create(array(
      'uuid' => $this->randomMachineName(),
      'name' => $this->randomMachineName(),
    ));
    $composite->save();

    // Assert that there is only 1 revision of the composite entity.
    $composite_revisions_count = \Drupal::entityQuery('entity_test_composite')
      ->condition('uuid', $composite->uuid())
      ->allRevisions()
      ->count()
      ->accessCheck(TRUE)
      ->execute();
    $this->assertEquals(1, $composite_revisions_count);

    // Create a node with a reference to the test composite entity.
    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::create(array(
      'title' => $this->randomMachineName(),
      'type' => 'article',
    ));
    $node->save();
    $node->set('composite_reference', $composite);
    $this->assertTrue($node->hasTranslationChanges());
    $node->save();

    // Assert that there is only 1 revision when creating a node.
    $node_revisions_count = \Drupal::entityQuery('node')
      ->condition('nid', $node->id())
      ->allRevisions()
      ->count()
      ->accessCheck(TRUE)
      ->execute();
    $this->assertEquals(1, $node_revisions_count);
    // Assert there is no new composite revision after creating a host entity.
    $composite_revisions_count = \Drupal::entityQuery('entity_test_composite')
      ->condition('uuid', $composite->uuid())
      ->allRevisions()
      ->count()
      ->accessCheck(TRUE)
      ->execute();
    $this->assertEquals(1, $composite_revisions_count);

    // Verify the value of parent type and id after create a node.
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertEquals($node->getEntityTypeId(), $composite->parent_type->value);
    $this->assertEquals($node->id(), $composite->parent_id->value);
    $this->assertEquals('composite_reference', $composite->parent_field_name->value);
    // Create second revision of the node.
    $original_composite_revision = $node->composite_reference[0]->target_revision_id;
    $original_node_revision = $node->getRevisionId();
    $node->setTitle('2nd revision');
    $node->setNewRevision();
    $node->save();
    $node = Node::load($node->id());
    // Check the revision of the node.
    $this->assertEquals('2nd revision', $node->getTitle(), 'New node revision has changed data.');
    $this->assertNotEquals($original_composite_revision, $node->composite_reference[0]->target_revision_id, 'Composite entity got new revision when its host did.');

    // Make sure that there are only 2 revisions.
    $node_revisions_count = \Drupal::entityQuery('node')
      ->condition('nid', $node->id())
      ->allRevisions()
      ->count()
      ->accessCheck(TRUE)
      ->execute();
    $this->assertEquals(2,$node_revisions_count);

    // Revert to first revision of the node.
    $node = $this->entityTypeManager->getStorage('node')->loadRevision($original_node_revision);
    $node->setNewRevision();
    $node->isDefaultRevision(TRUE);
    $node->save();
    $node = Node::load($node->id());
    // Check the revision of the node.
    $this->assertNotEquals('2nd revision', $node->getTitle(), 'Node did not keep changed title after reversion.');
    $this->assertNotEquals($original_composite_revision, $node->composite_reference[0]->target_revision_id, 'Composite entity got new revision when its host reverted to an old revision.');

    $node_storage = $this->entityTypeManager->getStorage('node');
    // Test that removing composite references results in translation changes.
    $node->set('composite_reference', []);
    $this->assertTrue($node->hasTranslationChanges());

    // Test that changing composite reference results in translation changes.
    $changed_composite_reference = $composite;
    $changed_composite_reference->set('name', 'Changing composite reference');
    $this->assertTrue((bool) $changed_composite_reference->isRevisionTranslationAffected());

    $node->set('composite_reference', $changed_composite_reference);
    $node->setNewRevision();
    $this->assertTrue($node->hasTranslationChanges());
    $node->save();
    $nid = $node->id();
    $node_storage->resetCache([$nid]);
    /** @var \Drupal\node\NodeInterface $node */
    $node = $node_storage->load($nid);

    // Check the composite has changed.
    $this->assertEquals('Changing composite reference', $node->get('composite_reference')->entity->getName());

    // Make sure the node has 4 revisions.
    $node_revisions_count = $node_storage->getQuery()
      ->condition('nid', $nid)
      ->allRevisions()
      ->count()
      ->accessCheck(TRUE)
      ->execute();
    $this->assertEquals(4, $node_revisions_count);

    // Make sure the node has no revision with revision translation affected
    // flag set to NULL.
    $node_revisions_count = $node_storage->getQuery()
      ->condition('nid', $nid)
      ->allRevisions()
      ->condition('revision_translation_affected', NULL, 'IS NULL')
      ->accessCheck(TRUE)
      ->count()
      ->execute();
    $this->assertEquals(0, $node_revisions_count, 'Node has a revision with revision translation affected set to NULL');

    // Revert the changes to avoid interfering with the delete test.
    $node->set('composite_reference', $composite);

    // Test that the composite entity is deleted when its parent is deleted.
    $node->delete();
    $this->assertNotNull(EntityTestCompositeRelationship::load($composite->id()));

    $this->cron->run();
    $this->assertNull(EntityTestCompositeRelationship::load($composite->id()));

    // Test that the deleting composite entity does not break the parent entity
    // when creating a new revision.
    $composite = EntityTestCompositeRelationship::create([
      'name' => $this->randomMachineName(),
    ]);
    $composite->save();
    // Create a node with a reference to the test composite entity.
    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::create([
      'title' => $this->randomMachineName(),
      'type' => 'article',
      'composite_reference' => $composite,
    ]);
    $node->save();
    // Delete the composite entity.
    $composite->delete();
    // Re-apply the field item values to unset the computed "entity" property.
    $field_item = $node->get('composite_reference')->get(0);
    $field_item->setValue($field_item->getValue(), FALSE);

    $new_revision = $this->entityTypeManager->getStorage('node')->createRevision($node);
    $this->assertTrue($new_revision->get('composite_reference')->isEmpty());
  }

  /**
   * Tests composite relationship with translations and an untranslatable field.
   */
  function testCompositeRelationshipWithTranslationNonTranslatableField() {

    ConfigurableLanguage::createFromLangcode('de')->save();

    // Create the test composite entity with a translation.
    $composite = EntityTestCompositeRelationship::create(array(
      'uuid' => $this->randomMachineName(),
      'name' => $this->randomMachineName(),
    ));
    $composite->addTranslation('de', $composite->toArray());
    $composite->save();


    // Create a node with a reference to the test composite entity.
    $node = Node::create(array(
      'title' => $this->randomMachineName(),
      'type' => 'article',
      'composite_reference' => $composite,
    ));
    $node->addTranslation('de', $node->toArray());
    $node->save();

    // Verify the value of parent type and id after create a node.
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertEquals($node->getEntityTypeId(), $composite->parent_type->value);
    $this->assertEquals($node->id(), $composite->parent_id->value);
    $this->assertEquals('composite_reference', $composite->parent_field_name->value);
    $this->assertTrue($composite->hasTranslation('de'));

    // Test that the composite entity is not deleted when the german translation
    // of the parent is deleted.
    $node->removeTranslation('de');
    $node->save();
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertNotNull($composite);
    $this->assertFalse($composite->hasTranslation('de'));

    // Change the language of the entity, ensure that doesn't try to delete
    // the default translation.
    $node->set('langcode', 'de');
    $node->save();
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertNotNull($composite);

    // Test that the composite entity is deleted when its parent is deleted.
    $node->delete();
    $this->cron->run();
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertNull($composite);
  }

  /**
   * Tests composite relationship with translations and a translatable field.
   */
  function testCompositeRelationshipWithTranslationTranslatableField() {
    $field_config = FieldConfig::loadByName('node', 'article', 'composite_reference');
    $field_config->setTranslatable(TRUE);
    $field_config->save();

    ConfigurableLanguage::createFromLangcode('de')->save();

    // Create the test composite entity with a translation.
    $composite = EntityTestCompositeRelationship::create(array(
      'uuid' => $this->randomMachineName(),
      'name' => $this->randomMachineName(),
    ));
    $composite->addTranslation('de', $composite->toArray());
    $composite->save();

    // Create a node with a reference to the test composite entity.
    $node = Node::create(array(
      'title' => $this->randomMachineName(),
      'type' => 'article',
      'composite_reference' => $composite,
    ));
    $node->addTranslation('de', $node->toArray());
    $node->save();

    // Verify the value of parent type and id after create a node.
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertEquals($node->getEntityTypeId(), $composite->parent_type->value);
    $this->assertEquals($node->id(), $composite->parent_id->value);
    $this->assertEquals('composite_reference', $composite->parent_field_name->value);

    // Test that the composite entity is not deleted when the German parent
    // translation is removed.
    $node->removeTranslation('de');
    $node->save();
    $this->cron->run();
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertNotNull($composite);

    // Test that the composite entity is deleted when its parent is deleted.
    $node->delete();
    $this->cron->run();
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertNull($composite);
  }

  /**
   * Tests composite relationship with revisions.
   */
  function testCompositeRelationshipWithRevisions() {

    // Create the test composite entity with a translation.
    $composite = EntityTestCompositeRelationship::create(array(
      'uuid' => $this->randomMachineName(),
      'name' => $this->randomMachineName(),
    ));
    $composite->save();

    // Create a node with a reference to the test composite entity.
    $node = Node::create(array(
      'title' => $this->randomMachineName(),
      'type' => 'article',
      'composite_reference' => $composite,
    ));
    $node->save();


    // Verify the value of parent type and id after create a node.
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $composite_original_revision_id = $composite->getRevisionId();
    $node_original_revision_id = $node->getRevisionId();
    $this->assertEquals($node->getEntityTypeId(), $composite->parent_type->value);
    $this->assertEquals($node->id(), $composite->parent_id->value);
    $this->assertEquals('composite_reference', $composite->parent_field_name->value);

    $node->setNewRevision(TRUE);
    $node->save();
    // Ensure that we saved a new revision ID.
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertNotEquals($composite_original_revision_id, $composite->getRevisionId());

    // Test that deleting the first revision does not delete the composite.
    $this->entityTypeManager->getStorage('node')->deleteRevision($node_original_revision_id);
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertNotNull($composite);

    // Ensure that the composite revision was deleted as well.
    $composite_revision = $this->entityTypeManager->getStorage('entity_test_composite')->loadRevision($composite_original_revision_id);
    $this->assertNull($composite_revision);

    // Test that the composite entity is deleted when its parent is deleted.
    $node->delete();
    $this->cron->run();
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertNull($composite);
  }

  /**
   * Tests that the composite revision is not deleted if it is the default one.
   */
  function testCompositeRelationshipDefaultRevision() {
    // Create a node with a reference to a test composite entity.
    $composite = EntityTestCompositeRelationship::create([
      'uuid' => $this->randomMachineName(),
      'name' => $this->randomMachineName(),
    ]);
    $composite->save();
    $node = Node::create([
      'title' => $this->randomMachineName(),
      'type' => 'article',
      'composite_reference' => $composite,
    ]);
    $node->save();

    $composite = EntityTestCompositeRelationship::load($composite->id());
    $composite_original_revision_id = $composite->getRevisionId();
    $node_original_revision_id = $node->getRevisionId();

    // Set a new revision, composite entity should have a new revision as well.
    $node->setNewRevision(TRUE);
    $node->save();
    // Ensure that we saved a new revision ID.
    $composite2 = EntityTestCompositeRelationship::load($composite->id());
    $composite2_rev_id = $composite2->getRevisionId();
    $this->assertNotEquals($composite2_rev_id, $composite_original_revision_id);

    // Revert default composite entity revision to the original revision.
    $composite_original = $this->entityTypeManager->getStorage('entity_test_composite')->loadRevision($composite_original_revision_id);
    $composite_original->isDefaultRevision(TRUE);
    $composite_original->save();
    // Check the default composite revision is the original composite revision.
    $this->assertEquals($composite_original_revision_id, $composite_original->getrevisionId());

    // Test deleting the first node revision, referencing to the default
    // composite revision, does not delete the default composite revision.
    $this->entityTypeManager->getStorage('node')->deleteRevision($node_original_revision_id);
    $composite_default = EntityTestCompositeRelationship::load($composite_original->id());
    $this->assertNotNull($composite_default);
    $composite_default_revision = $this->entityTypeManager->getStorage('entity_test_composite')->loadRevision($composite_original->getrevisionId());
    $this->assertNotNull($composite_default_revision);
    // Ensure the second revision still exists.
    $composite2_revision = $this->entityTypeManager->getStorage('entity_test_composite')->loadRevision($composite2_rev_id);
    $this->assertNotNull($composite2_revision);
  }

  /**
   * Tests that the composite revision is not deleted if it is still in use.
   */
  function testCompositeRelationshipDuplicatedRevisions() {
    // Create a node with a reference to a test composite entity.
    $composite = EntityTestCompositeRelationship::create([
      'uuid' => $this->randomMachineName(),
      'name' => $this->randomMachineName(),
    ]);
    $composite->save();
    $node = Node::create([
      'title' => $this->randomMachineName(),
      'type' => 'article',
      'composite_reference' => $composite,
    ]);
    $node->save();

    $composite = EntityTestCompositeRelationship::load($composite->id());
    $composite_original_revision_id = $composite->getRevisionId();
    $node_original_revision_id = $node->getRevisionId();

    // Set a new revision, composite entity should have a new revision as well.
    $node->setNewRevision(TRUE);
    $node->save();
    // Ensure that we saved a new revision ID.
    $composite2 = EntityTestCompositeRelationship::load($composite->id());
    $composite2_rev_id = $composite2->getRevisionId();
    $this->assertNotEquals($composite2_rev_id, $composite_original_revision_id);

    // Set the new node revision to reference to the original composite
    // revision as well to test this composite revision will not be deleted.
    $this->database->update('node__composite_reference')
      ->fields(['composite_reference_target_revision_id' => $composite_original_revision_id])
      ->condition('revision_id', $node->getRevisionId())
      ->execute();
    $this->database->update('node_revision__composite_reference')
      ->fields(['composite_reference_target_revision_id' => $composite_original_revision_id])
      ->condition('revision_id', $node->getRevisionId())
      ->execute();

    // Test deleting the first revision does not delete the composite.
    $this->entityTypeManager->getStorage('node')->deleteRevision($node_original_revision_id);
    $composite2 = EntityTestCompositeRelationship::load($composite2->id());
    $this->assertNotNull($composite2);

    // Ensure the original composite revision is not deleted because it is
    // still referenced by the second node revision.
    $composite_original_revision = $this->entityTypeManager->getStorage('entity_test_composite')->loadRevision($composite_original_revision_id);
    $this->assertNotNull($composite_original_revision);
    // Ensure the second revision still exists.
    $composite2_revision = $this->entityTypeManager->getStorage('entity_test_composite')->loadRevision($composite2_rev_id);
    $this->assertNotNull($composite2_revision);

    // Test that the composite entity is deleted when its parent is deleted.
    $node->delete();
    $this->cron->run();
    $composite = EntityTestCompositeRelationship::load($composite2->id());
    $this->assertNull($composite);
  }

  /**
   * Tests the composite entity is deleted after removing its reference.
   */
  public function testCompositeDeleteAfterRemovingReference() {
    list($composite, $node) = $this->assignCompositeToNode();

    // Remove reference to the composite entity from the node.
    $node->set('composite_reference', NULL);
    $node->save();

    // Verify that the composite entity is not yet removed after deleting the
    // parent.
    $node->delete();
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertNotNull($composite);

    // Verify that the composite entity is removed after running cron.
    $this->cron->run();
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertNull($composite);
  }

  /**
   * Tests the composite entity is deleted after removing its reference.
   *
   * Includes revisions on the host entity.
   */
  public function testCompositeDeleteAfterRemovingReferenceWithRevisions() {
    list($composite, $node) = $this->assignCompositeToNode();

    // Remove reference to the composite entity from the node in a new revision.
    $node->set('composite_reference', NULL);
    $node->setNewRevision();
    $node->save();
    $composite = EntityTestCompositeRelationship::load($composite->id());
    // Verify the composite entity is not removed on nodes with revisions.
    $this->assertNotNull($composite);

    // Verify that the composite entity is not yet removed after deleting the
    // parent.
    $node->delete();
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertNotNull($composite);

    // Verify that the composite entity is removed after running cron.
    $this->cron->run();
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertNull($composite);
  }

  /**
   * Tests the composite entity is not deleted when changing parents.
   *
   * Includes revisions on the host entity.
   */
  public function testCompositeDeleteAfterChangingParent() {
    list($composite, $node) = $this->assignCompositeToNode();
    // Remove reference to the composite entity from the node.
    $node->set('composite_reference', NULL);
    $node->setNewRevision();
    $node->save();

    // Setting a new revision of the composite entity in the second node.
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $composite->setNewRevision(TRUE);
    $composite->save();
    $second_node = Node::create([
      'title' => 'Second node',
      'type' => 'article',
      'composite_reference' => $composite,
    ]);
    $second_node->save();
    // Remove reference to the composite entity from the node.
    $second_node->set('composite_reference', NULL);
    $second_node->setNewRevision(TRUE);
    $second_node->save();
    // Verify the composite entity is not removed on nodes with revisions.
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertNotNull($composite);
    // Verify the amount of revisions of each entity.
    $this->assertRevisionCount(2, 'entity_test_composite', $composite->id());
    $this->assertRevisionCount(2, 'node', $node->id());
    $this->assertRevisionCount(2, 'node', $second_node->id());
    // Test that the composite entity is not deleted when its new parent is
    // deleted, since it is still being used in a previous revision with a
    // different parent.
    $second_node->delete();
    $this->cron->run();
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertNotNull($composite);

    // Delete the parent of the previous revision.
    $node->delete();

    // Verify that the composite entity is removed after running cron.
    $this->cron->run();
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertNull($composite);
  }

  /**
   * Composite entity with revisions isn't deleted when changing parents.
   *
   * Includes revisions on the host entity.
   */
  public function testCompositeDeleteRevisionAfterChangingParent() {
    list($composite, $node) = $this->assignCompositeToNode();
    // Remove reference to the composite entity from the node.
    $node->set('composite_reference', NULL);
    $node->setNewRevision();
    $node->save();

    // Setting a new revision of the composite entity in the second node.
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $composite->setNewRevision(TRUE);
    $composite->save();
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $second_node = Node::create([
      'title' => 'Second node',
      'type' => 'article',
      'composite_reference' => $composite,
    ]);
    $second_node->save();
    // Remove reference to the composite entity from the node.
    $second_node->set('composite_reference', NULL);
    $second_node->setNewRevision(TRUE);
    $second_node->save();
    // Verify the composite entity is not removed on nodes with revisions.
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertNotNull($composite);
    // Verify the amount of revisions of each entity.
    $this->assertRevisionCount(2, 'entity_test_composite', $composite->id());
    $this->assertRevisionCount(2, 'node', $node->id());
    $this->assertRevisionCount(2, 'node', $second_node->id());
    // Test that the composite entity is not deleted when its old parent is
    // deleted.
    $node->delete();
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertNotNull($composite);

    // Verify that the composite entity is not removed after running cron but
    // the previous unused revision is deleted.
    $this->cron->run();
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertNotNull($composite);
    $this->assertRevisionCount(1, 'entity_test_composite', $composite->id());
  }

  /**
   * Tests the composite entity is not deleted when duplicating host entity.
   *
   * Includes revisions on the host entity.
   */
  public function testCompositeDeleteAfterDuplicatingParent() {
    list($composite, $node) = $this->assignCompositeToNode();
    $node->setNewRevision(TRUE);
    $node->save();

    // Create a duplicate of the node.
    $duplicate_node = $node->createDuplicate();
    $duplicate_node->save();
    $duplicate_node->setNewRevision(TRUE);
    $duplicate_node->save();

    // Verify the amount of revisions of each entity.
    $this->assertRevisionCount(3, 'entity_test_composite', $composite->id());
    $this->assertRevisionCount(2, 'node', $node->id());
    $this->assertRevisionCount(2, 'node', $duplicate_node->id());
    // Test that the composite entity is not deleted when the duplicate is
    // deleted.
    $duplicate_node->delete();
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertNotNull($composite);

    $this->cron->run();
    $composite = EntityTestCompositeRelationship::load($composite->id());
    $this->assertNotNull($composite);
  }

  /**
   * Asserts the revision count of a certain entity.
   *
   * @param int $expected
   *   The expected count.
   * @param string $entity_type_id
   *   The entity type ID, e.g. node.
   * @param int $entity_id
   *   The entity ID.
   */
  protected function assertRevisionCount($expected, $entity_type_id, $entity_id) {
    $id_field = \Drupal::entityTypeManager()
      ->getDefinition($entity_type_id)
      ->getKey('id');
    $revision_count = \Drupal::entityQuery($entity_type_id)
      ->condition($id_field, $entity_id)
      ->allRevisions()
      ->count()
      ->accessCheck(TRUE)
      ->execute();
    $this->assertEquals($expected, $revision_count);
  }

  /**
   * Creates and assigns the composite entity to a node.
   *
   * @param string $node_type
   *   The node type.
   *
   * @return array
   *   An array containing a composite and a node entity.
   */
  protected function assignCompositeToNode($node_type = 'article') {
    $composite = EntityTestCompositeRelationship::create([
      'uuid' => $this->randomMachineName(),
      'name' => $this->randomMachineName(),
    ]);
    $composite->save();
    $node = Node::create([
      'title' => $this->randomMachineName(),
      'type' => $node_type,
      'composite_reference' => $composite,
    ]);
    $node->save();

    return [$composite, $node];
  }

}
