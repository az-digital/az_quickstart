<?php

namespace Drupal\Tests\webform_example_element_properties\Functiona;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for webform example element properties.
 *
 * @group webform_example_element_properties
 */
class WebformExampleElementPropertiesTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'webform',
    'webform_ui',
    'webform_example_element_properties',
  ];

  /**
   * Tests element custom properties.
   */
  public function testCustomProperties() {
    $assert_session = $this->assertSession();

    // Create and login admin user.
    $admin_user = $this->drupalCreateUser([
      'administer webform',
    ]);
    $this->drupalLogin($admin_user);

    // Get Webform storage.
    $webform_storage = \Drupal::entityTypeManager()->getStorage('webform');

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $webform_storage->load('contact');

    // Set name element.
    $name_element = [
      '#type' => 'textfield',
      '#title' => 'Your Name',
      '#default_value' => '[current-user:display-name]',
      '#required' => TRUE,
    ];

    // Check that name element render array does not contain custom property
    // or data.
    $this->assertEquals($webform->getElementDecoded('name'), $name_element);

    // Check that name input does not contain custom data.
    $this->drupalGet('/webform/contact');
    $assert_session->responseContains('<input data-drupal-selector="edit-name" type="text" id="edit-name" name="name" value="' . htmlentities($admin_user->label()) . '" size="60" maxlength="255" class="form-text required" required="required" aria-required="true" />');

    // Submit empty custom property and data.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/name/edit');
    $edit = ['properties[custom_data]' => ''];
    $this->submitForm($edit, 'Save');

    // Get updated contact webform.
    $webform_storage->resetCache();
    $webform = $webform_storage->load('contact');

    // Check that name element render array still does not contain custom
    // property or data.
    $this->assertEquals($webform->getElementDecoded('name'), $name_element);

    // Add custom property and data.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/name/edit');
    $edit = ['properties[custom_data]' => 'custom-data'];
    $this->submitForm($edit, 'Save');

    // Get updated contact webform.
    $webform_storage->resetCache();
    $webform = $webform_storage->load('contact');

    // Check that name element does contain custom property or data.
    $name_element += [
      '#custom_data' => 'custom-data',
    ];
    $this->assertEquals($webform->getElementDecoded('name'), $name_element);

    // Check that name input does contain custom data.
    $this->drupalGet('/webform/contact');
    $assert_session->responseContains('<input data-custom="custom-data" data-drupal-selector="edit-name" type="text" id="edit-name" name="name" value="' . htmlentities($admin_user->label()) . '" size="60" maxlength="255" class="form-text required" required="required" aria-required="true" />');
  }

}
