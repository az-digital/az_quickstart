<?php

namespace Drupal\Tests\entity_reference_revisions\Functional;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Tests the entity_reference_revisions configuration.
 *
 * @group entity_reference_revisions
 */
class EntityReferenceRevisionsAdminTest extends BrowserTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = array(
    'node',
    'field',
    'entity_reference_revisions',
    'field_ui',
    'block',
  );

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create paragraphs and article content types.
    $this->drupalCreateContentType(array('type' => 'entity_revisions', 'name' => 'Entity revisions'));
    $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    // Place the breadcrumb, tested in fieldUIAddNewField().
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $admin_user = $this->drupalCreateUser(array(
      'administer site configuration',
      'administer nodes',
      'create article content',
      'create entity_revisions content',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer node form display',
      'edit any article content',
    ));
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests the entity reference revisions configuration.
   */
  public function testEntityReferenceRevisions() {
    // Create a test target node used as entity reference by another test node.
    $node_target = Node::create([
      'title' => 'Target node',
      'type' => 'article',
      'body' => 'Target body text',
      'uuid' => '2d04c2b4-9c3d-4fa6-869e-ecb6fa5c9410',
    ]);
    $node_target->save();

    // Add an entity reference revisions field to entity_revisions content type
    // with $node_target as default value.
    $storage_edit = ['settings[target_type]' => 'node', 'cardinality' => '-1'];
    $field_edit = [
      'settings[handler_settings][target_bundles][article]' => TRUE,
      'default_value_input[field_entity_reference_revisions][0][target_id]' => $node_target->label() . ' (' . $node_target->id() . ')',
    ];
    if (version_compare(\Drupal::VERSION, '10.1', '>=')) {
      $field_edit['set_default_value'] = TRUE;
    }
    static::fieldUIAddNewField('admin/structure/types/manage/entity_revisions', 'entity_reference_revisions', 'Entity reference revisions', 'entity_reference_revisions', $storage_edit, $field_edit);
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    $this->assertSession()->pageTextContains('Saved Entity reference revisions configuration.');

    // Resave the target node, so that the default revision is not the one we
    // want to use.
    $revision_id = $node_target->getRevisionId();
    $node_target_after = Node::load($node_target->id());
    $node_target_after->setNewRevision();
    $node_target_after->save();
    $this->assertTrue($node_target_after->getRevisionId() != $revision_id);

    // Create an article.
    $title = $this->randomMachineName();
    $edit = array(
      'title[0][value]' => $title,
      'body[0][value]' => 'Revision 1',
    );
    $this->drupalGet('node/add/article');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains($title);
    $this->assertSession()->pageTextContains('Revision 1');
    $node = $this->drupalGetNodeByTitle($title);

    // Check if when creating an entity revisions content the default entity
    // reference is set, add also the above article as a new reference.
    $this->drupalGet('node/add/entity_revisions');
    $this->assertSession()->fieldValueEquals('field_entity_reference_revisions[0][target_id]', $node_target->label() . ' (' . $node_target->id() . ')');
    $edit = [
      'title[0][value]' => 'Entity reference revision content',
      'field_entity_reference_revisions[1][target_id]' => $node->label() . ' (' . $node->id() . ')',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->linkByHrefExists('node/' . $node_target->id());
    $this->assertSession()->pageTextContains('Entity revisions Entity reference revision content has been created.');
    $this->assertSession()->pageTextContains('Entity reference revision content');
    $this->assertSession()->pageTextContains($title);
    $this->assertSession()->pageTextContains('Revision 1');

    // Create 2nd revision of the article.
    $edit = array(
      'body[0][value]' => 'Revision 2',
      'revision' => TRUE,
    );
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains($title);
    $this->assertSession()->pageTextContains('Revision 2');

    // View the Entity reference content and make sure it still has revision 1.
    $node = $this->drupalGetNodeByTitle('Entity reference revision content');
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextContains($title);
    $this->assertSession()->pageTextContains('Revision 1');
    $this->assertSession()->pageTextNotContains('Revision 2');

    // Make sure the non-revisionable entities are not selectable as referenced
    // entities.
    $this->drupalGet('admin/structure/types/manage/entity_revisions/fields/add-field');
    if (version_compare(\Drupal::VERSION, '10.2', '>=')) {
      $selected_group = [
        'new_storage_type' => 'reference',
      ];
      // The DeprecationHelper class is available from Drupal Core 10.1.x,
      // so no need for class_exists here.
      $submit = DeprecationHelper::backwardsCompatibleCall(\Drupal::VERSION, '10.3', fn() => "Continue", fn() => "Change field group");
      $this->submitForm($selected_group, $submit);
      $this->assertSession()->pageTextContains('Other (revisions)');
      $edit = array(
        'group_field_options_wrapper' => 'entity_reference_revisions',
        'label' => 'Entity reference revisions field',
        'field_name' => 'entity_ref_revisions_field',
      );
      $this->submitForm($edit, 'Continue');
      $this->assertSession()->optionNotExists('field_storage[subform][settings][target_type]', 'user');
      $this->assertSession()->optionExists('field_storage[subform][settings][target_type]', 'node');
    }
    else {
      $edit = array(
        'new_storage_type' => 'entity_reference_revisions',
        'label' => 'Entity reference revisions field',
        'field_name' => 'entity_ref_revisions_field',
      );
      $this->submitForm($edit, 'Save and continue');
      $this->assertSession()->optionNotExists('edit-settings-target-type', 'user');
      $this->assertSession()->optionExists('edit-settings-target-type', 'node');
    }

    // Check ERR default value and property definitions label are set properly.
    $field_definition = $node->getFieldDefinition('field_entity_reference_revisions');
    $default_value = $field_definition->toArray()['default_value'];
    $this->assertEquals($node_target->uuid(), $default_value[0]['target_uuid']);
    $this->assertEquals($revision_id, $default_value[0]['target_revision_id']);
    $properties = $field_definition->getFieldStorageDefinition()->getPropertyDefinitions();
    $this->assertEquals('Content revision ID', (string) $properties['target_revision_id']->getLabel());
    $this->assertEquals('Content ID', (string) $properties['target_id']->getLabel());
    $this->assertEquals('Content', (string) $properties['entity']->getLabel());
  }

  /**
   * Tests target bundle settings for an entity reference revisions field.
   */
  public function testMultipleTargetBundles() {
    // Create a couple of content types for the ERR field to point to.
    $target_types = [];
    for ($i = 0; $i < 2; $i++) {
      $target_types[$i] = $this->drupalCreateContentType([
        'type' => strtolower($this->randomMachineName()),
        'name' => 'Test type ' . $i
      ]);
    }

    // Create a new field that can point to either target content type.
    $node_type_path = 'admin/structure/types/manage/entity_revisions';

    // Generate a random field name, must be only lowercase characters.
    $field_name = strtolower($this->randomMachineName());

    $field_edit = [];
    $storage_edit = ['settings[target_type]' => 'node', 'cardinality' => '-1'];
    $field_edit['settings[handler_settings][target_bundles][' . $target_types[0]->id() . ']'] = TRUE;
    $field_edit['settings[handler_settings][target_bundles][' . $target_types[1]->id() . ']'] = TRUE;

    $this->fieldUIAddNewField($node_type_path, $field_name, 'Entity reference revisions', 'entity_reference_revisions', $storage_edit, $field_edit);

    // Deleting one of these content bundles at this point should only delete
    // that bundle's body field. Test that there is no second field that will
    // be deleted.
    $this->drupalGet('/admin/structure/types/manage/' . $target_types[0]->id() . '/delete');
    $this->xpath('(//details[@id="edit-entity-deletes"]//ul[@data-drupal-selector="edit-field-config"]/li)[2]');
  }

}
