<?php

namespace Drupal\Tests\field_group\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests for managing display of entities.
 *
 * @group field_group
 */
class ManageDisplayTest extends BrowserTestBase {

  use FieldGroupTestTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'field_ui', 'field_group'];

  /**
   * Content type id.
   *
   * @var string
   */
  protected $type;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create test user.
    $admin_user = $this->drupalCreateUser([
      'access content',
      'administer content types',
      'administer node fields',
      'administer node form display',
      'administer node display',
      'bypass node access',
    ]);
    $this->drupalLogin($admin_user);

    // Create content type, with underscores.
    $type_name = 'll4ma_test';
    $type = $this->drupalCreateContentType([
      'name' => $type_name,
      'type' => $type_name,
    ]);
    $this->type = $type->id();

  }

  /**
   * Test the creation a group on the article content type.
   */
  public function testCreateGroup() {
    // Create random group name.
    $group_label = $this->randomString(8);
    $group_name_input = mb_strtolower($this->randomMachineName());
    $group_name = 'group_' . $group_name_input;
    $group_formatter = 'details';

    // Setup new group.
    $group = [
      'group_formatter' => $group_formatter,
      'label' => $group_label,
    ];

    $add_form_display = 'admin/structure/types/manage/' . $this->type . '/form-display/add-group';
    $this->drupalGet($add_form_display);
    $this->submitForm($group, 'Save and continue');
    $this->assertSession()->pageTextContains('Machine-readable name field is required.');

    // Add required field to form.
    $group['group_name'] = $group_name_input;

    // Add new group on the 'Manage form display' page.
    $this->drupalGet($add_form_display);
    $this->submitForm($group, 'Save and continue');
    $this->submitForm([], 'Create group');

    $this->assertSession()->responseContains($this->t('New group %label successfully created.', ['%label' => $group_label]));

    // Test if group is in the $groups array.
    $this->group = field_group_load_field_group($group_name, 'node', $this->type, 'form', 'default');
    $this->assertNotNull($group, 'Group was loaded');

    // Test if region key is set.
    $this->assertEquals('hidden', $this->group->region);

    // Add new group on the 'Manage display' page.
    $this->drupalGet('admin/structure/types/manage/' . $this->type . '/display/add-group');
    $this->submitForm($group, 'Save and continue');
    $this->submitForm([], 'Create group');

    $this->assertSession()->responseContains($this->t('New group %label successfully created.', ['%label' => $group_label]));

    // Test if group is in the $groups array.
    $loaded_group = field_group_load_field_group($group_name, 'node', $this->type, 'view', 'default');
    $this->assertNotNull($loaded_group, 'Group was loaded');
  }

  /**
   * Delete a group.
   */
  public function testDeleteGroup() {
    $data = [
      'format_type' => 'fieldset',
      'label' => 'testing',
    ];

    $group = $this->createGroup('node', $this->type, 'form', 'default', $data);

    $this->drupalGet('admin/structure/types/manage/' . $this->type . '/form-display/' . $group->group_name . '/delete');
    $this->submitForm([], 'Delete');
    $this->assertSession()->responseContains($this->t('The group %label has been deleted from the %type content type.', [
      '%label' => $group->label,
      '%type' => $this->type,
    ]));

    // Test that group is not in the $groups array.
    \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->resetCache();
    $loaded_group = field_group_load_field_group($group->group_name, 'node', $this->type, 'form', 'default');
    $this->assertNull($loaded_group, 'Group not found after deleting');

    $group = $this->createGroup('node', $this->type, 'view', 'default', $data);

    $this->drupalGet('admin/structure/types/manage/' . $this->type . '/display/' . $group->group_name . '/delete');
    $this->submitForm([], 'Delete');
    $this->assertSession()->responseContains($this->t('The group %label has been deleted from the %type content type.', [
      '%label' => $group->label,
      '%type' => $this->type,
    ]));

    // Test that group is not in the $groups array.
    \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->resetCache();
    $loaded_group = field_group_load_field_group($group->group_name, 'node', $this->type, 'view', 'default');
    $this->assertNull($loaded_group, 'Group not found after deleting');
  }

  /**
   * Nest a field underneath a group.
   */
  public function testNestField() {
    $data = [
      'format_type' => 'fieldset',
    ];

    $group = $this->createGroup('node', $this->type, 'form', 'default', $data);

    $edit = [
      'fields[body][parent]' => $group->group_name,
    ];
    $this->drupalGet('admin/structure/types/manage/' . $this->type . '/form-display');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->responseContains('Your settings have been saved.');

    $group = field_group_load_field_group($group->group_name, 'node', $this->type, 'form', 'default');
    $this->assertTrue(in_array('body', $group->children), $this->t('Body is a child of %group', ['%group' => $group->group_name]));
  }

}
