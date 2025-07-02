<?php

namespace Drupal\Tests\field_group\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\field_group\Functional\FieldGroupTestTrait;

/**
 * Test field_group user interface.
 *
 * @group field_group
 */
class FieldGroupUiTest extends WebDriverTestBase {

  use FieldGroupTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'field_ui', 'field_group'];

  /**
   * The current tested node type.
   *
   * @var string
   */
  protected $nodeType;

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
    $type_name = mb_strtolower($this->randomMachineName(8)) . '_test';
    $type = NodeType::create([
      'name' => $type_name,
      'type' => $type_name,
    ]);
    $type->save();
    $this->nodeType = $type->id();
  }

  /**
   * Test creation and editing trough the UI.
   */
  public function testCreateAndEdit() {
    foreach (['test_1', 'test_2'] as $name) {
      $this->drupalGet('admin/structure/types/manage/' . $this->nodeType . '/form-display/add-group');
      $page = $this->getSession()->getPage();

      // Type the label to activate the machine name field and the edit button.
      $page->fillField('group_formatter', 'details');
      $page->fillField('label', 'Test 1');
      // Wait for the machine name widget to be activated.
      $this->assertSession()->waitForElementVisible('css', 'button[type=button].link:contains(Edit)');
      // Activate the machine name text field.
      $page->pressButton('Edit');
      $page->fillField('Machine-readable name', $name);
      $page->pressButton('Save and continue');
      $page->pressButton('Create group');
    }

    // Update title in group 1.
    $page = $this->getSession()->getPage();
    $page->pressButton('group_test_1_group_settings_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $page->fillField('fields[group_test_1][settings_edit_form][settings][label]', 'Test 1 - Update');
    $page->pressButton('Update');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Update title in group 2.
    $page->pressButton('group_test_2_group_settings_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $page->fillField('fields[group_test_2][settings_edit_form][settings][label]', 'Test 2 - Update');
    $page->pressButton('Update');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Open group 1 again.
    $page->pressButton('group_test_1_group_settings_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->fieldValueEquals('fields[group_test_1][settings_edit_form][settings][label]', 'Test 1 - Update');
    $page->pressButton('Cancel');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $page->pressButton('Save');

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display */
    $display = EntityFormDisplay::load("node.{$this->nodeType}.default");
    $this->assertSame('Test 1 - Update', $display->getThirdPartySetting('field_group', 'group_test_1')['label']);
    $this->assertSame('Test 2 - Update', $display->getThirdPartySetting('field_group', 'group_test_2')['label']);
  }

}
