<?php

namespace Drupal\Tests\entity_reference_revisions\Functional;

use Drupal\Core\Site\Settings;
use Drupal\entity_composite_relationship_test\Entity\EntityTestCompositeRelationship;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests orphan composite revisions are properly removed.
 *
 * @group entity_reference_revisions
 */
class EntityReferenceRevisionsOrphanRemovalTest extends BrowserTestBase {

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
    'entity_composite_relationship_test'
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser([
      'delete orphan revisions',
    ]);
    $this->drupalLogin($this->adminUser);
    $this->insertRevisionableData();
    $this->insertNonRevisionableData();
  }

  /**
   * Tests that revisions that are no longer used are properly deleted.
   */
  public function testNotUsedRevisionDeletion() {
    $entity_test_composite_storage = \Drupal::entityTypeManager()->getStorage('entity_test_composite');

    $composite_entity_first = $entity_test_composite_storage->loadByProperties(['name' => 'first not used, second used']);
    $composite_entity_first = reset($composite_entity_first);
    $this->assertRevisionCount(2, 'entity_test_composite', $composite_entity_first->id());

    $composite_entity_second = $entity_test_composite_storage->loadByProperties(['name' => 'first used, second not used']);
    $composite_entity_second = reset($composite_entity_second);
    $this->assertRevisionCount(2, 'entity_test_composite', $composite_entity_second->id());

    $composite_entity_third = $entity_test_composite_storage->loadByProperties(['name' => 'first not used, second not used']);
    $composite_entity_third = reset($composite_entity_third);
    $this->assertRevisionCount(2, 'entity_test_composite', $composite_entity_third->id());

    $composite_entity_fourth = $entity_test_composite_storage->loadByProperties(['name' => '1st filled not, 2nd filled not']);
    $composite_entity_fourth = reset($composite_entity_fourth);
    $this->assertRevisionCount(2, 'entity_test_composite', $composite_entity_fourth->id());

    $composite_entity_fifth = $entity_test_composite_storage->loadByProperties(['name' => '1st not, 2nd used, 3rd not, 4th']);
    $composite_entity_fifth = reset($composite_entity_fifth);
    $this->assertRevisionCount(4, 'entity_test_composite', $composite_entity_fifth->id());

    $composite_entity_sixth = $entity_test_composite_storage->loadByProperties(['name' => 'wrong parent fields']);
    $composite_entity_sixth = reset($composite_entity_sixth);
    $this->assertRevisionCount(1, 'entity_test_composite', $composite_entity_sixth->id());

    // Test non revisionable parent entities.
    $composite_entity_seventh = $entity_test_composite_storage->loadByProperties(['name' => 'NR first not used, second used']);
    $composite_entity_seventh = reset($composite_entity_seventh);
    $this->assertRevisionCount(2, 'entity_test_composite', $composite_entity_seventh->id());

    $composite_entity_eighth = $entity_test_composite_storage->loadByProperties(['name' => 'NR first used, second not used']);
    $composite_entity_eighth = reset($composite_entity_eighth);
    $this->assertRevisionCount(2, 'entity_test_composite', $composite_entity_eighth->id());

    $composite_entity_ninth = $entity_test_composite_storage->loadByProperties(['name' => 'NR 1st not, 2nd, 3rd not, 4th']);
    $composite_entity_ninth = reset($composite_entity_ninth);
    $this->assertRevisionCount(3, 'entity_test_composite', $composite_entity_ninth->id());

    // Set the batch size to 1.
    $settings = Settings::getInstance() ? Settings::getAll() : [];
    $settings['entity_update_batch_size'] = 1;
    new Settings($settings);

    // Run the delete process through the form.
    $this->runDeleteForm();
    $this->assertSession()->pageTextContains('Test entity - composite relationship: Deleted 8 revisions (1 entities)');

    $this->assertRevisionCount(1, 'entity_test_composite', $composite_entity_first->id());
    $this->assertRevisionCount(2, 'entity_test_composite', $composite_entity_second->id());
    $this->assertRevisionCount(2, 'entity_test_composite', $composite_entity_third->id());
    $this->assertRevisionCount(0, 'entity_test_composite', $composite_entity_fourth->id());
    $this->assertRevisionCount(2, 'entity_test_composite', $composite_entity_fifth->id());
    $this->assertRevisionCount(1, 'entity_test_composite', $composite_entity_sixth->id());
    $this->assertRevisionCount(1, 'entity_test_composite', $composite_entity_seventh->id());
    $this->assertRevisionCount(2, 'entity_test_composite', $composite_entity_eighth->id());
    $this->assertRevisionCount(1, 'entity_test_composite', $composite_entity_ninth->id());
  }

  /**
   * Programmatically runs the 'Delete orphaned composite entities' form.
   */
  public function runDeleteForm() {
    $this->drupalGet('admin/config/system/delete-orphans');
    $this->submitForm([], 'Delete orphaned composite revisions');
    $this->checkForMetaRefresh();
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
    $id_field = \Drupal::entityTypeManager()->getDefinition($entity_type_id)->getKey('id');
    $revision_count = \Drupal::entityQuery($entity_type_id)
      ->condition($id_field, $entity_id)
      ->allRevisions()
      ->count()
      ->accessCheck(TRUE)
      ->execute();
    $this->assertEquals($expected, $revision_count);
  }

  /**
   * Inserts revisionable entities needed for testing.
   */
  public function insertRevisionableData() {
    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    NodeType::create(['type' => 'revisionable', 'new_revision' => TRUE])->save();
    // Add a translatable field and a not translatable field to both content
    // types.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_composite_entity',
      'entity_type' => 'node',
      'type' => 'entity_reference_revisions',
      'settings' => [
        'target_type' => 'entity_test_composite'
      ],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'revisionable',
      'translatable' => FALSE,
    ]);
    $field->save();

    // Scenario 1: A composite with a default revision that is referenced and an
    // old revision that is not. Result: Only the old revision is deleted.
    $composite_entity_first = EntityTestCompositeRelationship::create([
      'name' => 'first not used, second used',
      'parent_id' => 1000,
      'parent_type' => 'node',
      'parent_field_name' => 'field_composite_entity',
    ]);
    $composite_entity_first->save();
    $composite_entity_first = EntityTestCompositeRelationship::load($composite_entity_first->id());
    $composite_entity_first->setNewRevision(TRUE);
    $composite_entity_first->save();
    $node = $this->drupalCreateNode([
      'type' => 'revisionable',
      'title' => 'First composite',
      'field_composite_entity' => $composite_entity_first,
    ]);
    $node->save();

    // Scenario 2: A composite with an old revision that is used and a default
    // revision that is not. Result: Nothing should be deleted.
    $composite_entity_second = EntityTestCompositeRelationship::create([
      'name' => 'first used, second not used',
    ]);
    $composite_entity_second->save();
    $node = $this->drupalCreateNode([
      'type' => 'revisionable',
      'title' => 'Second composite',
      'field_composite_entity' => $composite_entity_second,
    ]);
    $node->save();
    $node = $this->getNodeByTitle('Second composite');
    $node = $node_storage->createRevision($node);
    $node->set('field_composite_entity', NULL);
    $node->save();
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
      'parent_type' => 'node',
      'parent_field_name' => 'field_composite_entity',
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
      'parent_type' => 'node',
      'parent_field_name' => 'field_composite_entity',
    ]);
    $composite_entity_fifth->save();
    $composite_entity_fifth = EntityTestCompositeRelationship::load($composite_entity_fifth->id());
    $composite_entity_fifth->setNewRevision(TRUE);
    $composite_entity_fifth->save();
    $node = $this->drupalCreateNode([
      'type' => 'revisionable',
      'title' => 'Third composite',
      'field_composite_entity' => $composite_entity_fifth,
    ]);
    $node->save();
    $node = $this->getNodeByTitle('Third composite');
    $node = $node_storage->createRevision($node);
    $node->set('field_composite_entity', NULL);
    $node->save();
    $composite_entity_fifth = EntityTestCompositeRelationship::load($composite_entity_fifth->id());
    $composite_entity_fifth->setNewRevision(TRUE);
    $composite_entity_fifth->save();
    $node = $this->getNodeByTitle('Third composite');
    $node = $node_storage->createRevision($node);
    $node->set('field_composite_entity', $composite_entity_fifth);
    $node->save();

    // Scenario 6: A composite with wrong parent fields filled pointing to a non
    // existent parent (Parent 1). However, Parent 2 references it. Result: Must
    // not be deleted.
    $node = $this->drupalCreateNode([
      'type' => 'revisionable',
      'title' => 'DELETED composite',
    ]);
    $node->save();
    $composite_entity_sixth = EntityTestCompositeRelationship::create([
      'name' => 'wrong parent fields',
      'parent_id' => $node->id(),
      'parent_type' => 'node',
      'parent_field_name' => 'field_composite_entity',
    ]);
    $composite_entity_sixth->save();
    $node->delete();
    $node = $this->drupalCreateNode([
      'type' => 'revisionable',
      'title' => 'Fourth composite',
      'field_composite_entity' => $composite_entity_sixth,
    ]);
    $node->save();
  }

  /**
   * Inserts non revisionable entities needed for testing.
   */
  public function insertNonRevisionableData() {
    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    NodeType::create(['type' => 'non_revisionable', 'new_revision' => FALSE])->save();
    // Add a translatable field and a not translatable field to both content
    // types.
    $field_storage = FieldStorageConfig::loadByName('node', 'field_composite_entity');
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'non_revisionable',
      'translatable' => FALSE,
    ]);
    $field->save();

    // Scenario 1: A composite with a default revision that is referenced and an
    // old revision that is not. Result: Only the old revision is deleted.
    $composite_entity_first = EntityTestCompositeRelationship::create([
      'name' => 'NR first not used, second used',
      'parent_id' => 1001,
      'parent_type' => 'node',
      'parent_field_name' => 'field_composite_entity',
    ]);
    $composite_entity_first->save();
    $composite_entity_first = EntityTestCompositeRelationship::load($composite_entity_first->id());
    $composite_entity_first->setNewRevision(TRUE);
    $composite_entity_first->save();
    $node = $this->drupalCreateNode([
      'type' => 'non_revisionable',
      'title' => 'First composite',
      'field_composite_entity' => $composite_entity_first,
    ]);
    $node->save();

    // Scenario 2: A composite with an old revision that is used and a default
    // revision that is not. Result: Nothing should be deleted.
    $composite_entity_second = EntityTestCompositeRelationship::create([
      'name' => 'NR first used, second not used',
    ]);
    $composite_entity_second->save();
    $node = $this->drupalCreateNode([
      'type' => 'non_revisionable',
      'title' => 'Second composite',
      'field_composite_entity' => $composite_entity_second,
    ]);
    $node->save();
    $composite_entity_second = EntityTestCompositeRelationship::load($composite_entity_second->id());
    $composite_entity_second->setNewRevision(TRUE);
    $composite_entity_second->save();

    // Scenario 3: A composite with many revisions and 2 at least used. Result:
    // Delete all unused revisions.
    $composite_entity_third = EntityTestCompositeRelationship::create([
      'name' => 'NR 1st not, 2nd, 3rd not, 4th',
      'parent_id' => 1001,
      'parent_type' => 'node',
      'parent_field_name' => 'field_composite_entity',
    ]);
    $composite_entity_third->save();
    $composite_entity_third = EntityTestCompositeRelationship::load($composite_entity_third->id());
    $composite_entity_third->setNewRevision(TRUE);
    $composite_entity_third->save();
    $node = $this->drupalCreateNode([
      'type' => 'non_revisionable',
      'title' => 'Third composite',
      'field_composite_entity' => $composite_entity_third,
    ]);
    $node->save();
    $node = $this->getNodeByTitle('Third composite');
    $node->set('field_composite_entity', NULL);
    $node->save();
    $composite_entity_third = EntityTestCompositeRelationship::load($composite_entity_third->id());
    $composite_entity_third->setNewRevision(TRUE);
    $composite_entity_third->save();
    $node = $this->getNodeByTitle('Third composite');
    $node->set('field_composite_entity', $composite_entity_third);
    $node->save();
  }
}
