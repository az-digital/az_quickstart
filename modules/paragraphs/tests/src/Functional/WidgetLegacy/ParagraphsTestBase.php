<?php

namespace Drupal\Tests\paragraphs\Functional\WidgetLegacy;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;
use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;
use Drupal\Tests\paragraphs\Traits\ParagraphsCoreVersionUiTestTrait;
use Drupal\user\Entity\Role;

/**
 * Base class for tests.
 */
abstract class ParagraphsTestBase extends BrowserTestBase {

  use FieldUiTestTrait, ParagraphsCoreVersionUiTestTrait, ParagraphsTestBaseTrait;

  /**
   * Drupal user object created by loginAsAdmin().
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin_user = NULL;

  /**
   * List of permissions used by loginAsAdmin().
   *
   * @var array
   */
  protected $admin_permissions = [];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'paragraphs',
    'field',
    'field_ui',
    'block',
    'paragraphs_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->placeDefaultBlocks();

    $this->admin_permissions = [
      'administer content types',
      'administer node fields',
      'administer paragraphs types',
      'administer node form display',
      'administer paragraph fields',
      'administer paragraph form display',
      'bypass node access',
    ];
  }

  /**
   * Creates an user with admin permissions and log in.
   *
   * @param array $additional_permissions
   *   Additional permissions that will be granted to admin user.
   * @param bool $reset_permissions
   *   Flag to determine if default admin permissions will be replaced by
   *   $additional_permissions.
   *
   * @return object
   *   Newly created and logged in user object.
   */
  function loginAsAdmin($additional_permissions = [], $reset_permissions = FALSE) {
    $permissions = $this->admin_permissions;

    if ($reset_permissions) {
      $permissions = $additional_permissions;
    }
    elseif (!empty($additional_permissions)) {
      $permissions = array_merge($permissions, $additional_permissions);
    }

    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);
    return $this->admin_user;
  }

  /**
   * Sets the Paragraphs widget add mode.
   *
   * @param string $content_type
   *   Content type name where to set the widget mode.
   * @param string $paragraphs_field
   *   Paragraphs field to change the mode.
   * @param string $mode
   *   Mode to be set. ('dropdown', 'select' or 'button').
   */
  protected function setAddMode($content_type, $paragraphs_field, $mode) {
    $form_display = EntityFormDisplay::load('node.' . $content_type . '.default')
      ->setComponent($paragraphs_field, [
        'type' => 'entity_reference_paragraphs',
        'settings' => ['add_mode' => $mode]
      ]);
    $form_display->save();
  }

  /**
   * Sets the allowed Paragraphs types that can be added.
   *
   * @param string $content_type
   *   Content type name that contains the paragraphs field.
   * @param array $paragraphs_types
   *   Array of paragraphs types that will be modified.
   * @param bool $selected
   *   Whether or not the paragraphs types will be enabled.
   * @param string $paragraphs_field
   *   Paragraphs field name that does the reference.
   */
  protected function setAllowedParagraphsTypes($content_type, $paragraphs_types, $selected, $paragraphs_field) {
    $edit = [];
    $this->drupalGet('admin/structure/types/manage/' . $content_type . '/fields/node.' . $content_type . '.' . $paragraphs_field);
    foreach ($paragraphs_types as $paragraphs_type) {
      $edit['settings[handler_settings][target_bundles_drag_drop][' . $paragraphs_type . '][enabled]'] = $selected;
    }
    $this->submitForm($edit, 'Save settings');
  }

  /**
   * Sets the weight of a given Paragraphs type.
   *
   * @param string $content_type
   *   Content type name that contains the paragraphs field.
   * @param string $paragraphs_type
   *   ID of Paragraph type that will be modified.
   * @param int $weight
   *   Weight to be set.
   * @param string $paragraphs_field
   *   Paragraphs field name that does the reference.
   */
  protected function setParagraphsTypeWeight($content_type, $paragraphs_type, $weight, $paragraphs_field) {
    $this->drupalGet('admin/structure/types/manage/' . $content_type . '/fields/node.' . $content_type . '.' . $paragraphs_field);
    $edit['settings[handler_settings][target_bundles_drag_drop][' . $paragraphs_type . '][weight]'] = $weight;
    $this->submitForm($edit, 'Save settings');
  }

  /**
   * Sets the default paragraph type.
   *
   * @param $content_type
   *   Content type name that contains the paragraphs field.
   * @param $paragraphs_name
   *   Paragraphs name.
   * @param $paragraphs_field_name
   *   Paragraphs field name to be used.
   * @param $default_type
   *   Default paragraph type which should be set.
   */
  protected function setDefaultParagraphType($content_type, $paragraphs_name, $paragraphs_field_name, $default_type) {
    $this->drupalGet('admin/structure/types/manage/' . $content_type . '/form-display');
    $this->submitForm([], $paragraphs_field_name);
    $this->submitForm(['fields[' . $paragraphs_name . '][settings_edit_form][settings][default_paragraph_type]' => $default_type], 'Update');
    $this->submitForm([], 'Save');
  }

  /**
   * Removes the default paragraph type.
   *
   * @param $content_type
   *   Content type name that contains the paragraphs field.
   */
  protected function removeDefaultParagraphType($content_type) {
    $this->drupalGet('node/add/' . $content_type);
    $this->submitForm([], 'Remove');
    $this->submitForm([], 'Confirm removal');
    $this->assertSession()->pageTextNotContains('No paragraphs added yet.');
  }

  /**
   * Sets the Paragraphs widget display mode.
   *
   * @param string $content_type
   *   Content type name where to set the widget mode.
   * @param string $paragraphs_field
   *   Paragraphs field to change the mode.
   * @param string $mode
   *   Mode to be set. ('closed', 'preview' or 'open').
   *   'preview' is only allowed in the legacy widget. Use
   *   setParagraphsWidgetSettings for the stable widget, instead.
   */
  protected function setParagraphsWidgetMode($content_type, $paragraphs_field, $mode) {
    $this->drupalGet('admin/structure/types/manage/' . $content_type . '/form-display');
    $this->submitForm([], $paragraphs_field . '_settings_edit');
    $this->submitForm(['fields[' . $paragraphs_field . '][settings_edit_form][settings][edit_mode]' => $mode], 'Update');
    $this->submitForm([], 'Save');
  }

}
