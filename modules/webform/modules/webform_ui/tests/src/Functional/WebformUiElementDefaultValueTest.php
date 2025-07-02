<?php

namespace Drupal\Tests\webform_ui\Functional;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for webform UI element.
 *
 * @group webform_ui
 */
class WebformUiElementDefaultValueTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_ui'];

  /**
   * Tests element.
   */
  public function testElementDefaultValue() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    /* ********************************************************************** */
    // Single text field.
    /* ********************************************************************** */

    // Check validation when trying to set default value.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add/textfield');
    $this->submitForm([], 'Set default value');
    $assert_session->responseContains('Key field is required.');
    $assert_session->responseContains('Title field is required.');

    // Check set default value generates a single textfield element.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add/textfield');
    $edit = [
      'key' => 'textfield',
      'properties[title]' => 'textfield',
    ];
    $this->submitForm($edit, 'Set default value');
    $assert_session->responseContains('<label for="edit-default-value">textfield</label>');
    $assert_session->fieldValueEquals('default_value', '');

    // Check setting the text field's default value.
    $this->submitForm(['default_value' => '{default value}'], 'Update default value');
    $assert_session->fieldValueEquals('properties[default_value]', '{default value}');

    // Check clearing the text field's default value.
    $this->submitForm([], 'Clear default value');
    $assert_session->fieldValueEquals('properties[default_value]', '');

    /* ********************************************************************** */
    // Multiple text field.
    /* ********************************************************************** */

    // Check set default value generates a multiple textfield element.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add/textfield');
    $edit = [
      'key' => 'textfield',
      'properties[title]' => 'textfield',
      'properties[multiple][container][cardinality]' => '-1',
    ];
    $this->submitForm($edit, 'Set default value');
    $assert_session->fieldValueEquals('default_value[items][0][_item_]', '');

    // Check setting the text field's default value.
    $this->submitForm(['default_value[items][0][_item_]' => '{default value}'], 'Update default value');
    $assert_session->fieldValueEquals('properties[default_value]', '{default value}');

    // Check clearing the text field's default value.
    $this->submitForm([], 'Clear default value');
    $assert_session->fieldValueEquals('properties[default_value]', '');

    /* ********************************************************************** */
    // Single address (composite) field.
    /* ********************************************************************** */

    // Check set default value generates a single address element.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add/webform_address');
    $assert_session->fieldValueEquals('properties[default_value]', '');
    $edit = [
      'key' => 'address',
      'properties[title]' => 'address',
    ];
    $this->submitForm($edit, 'Set default value');
    $assert_session->fieldValueEquals('default_value[address]', '');
    $assert_session->fieldValueEquals('default_value[address_2]', '');

    // Check setting the address' default value.
    $edit = [
      'default_value[address]' => '{address}',
      'default_value[address_2]' => '{address_2}',
    ];
    $this->submitForm($edit, 'Update default value');
    $assert_session->responseContains('address: &#039;{address}&#039;
address_2: &#039;{address_2}&#039;
city: &#039;&#039;
state_province: &#039;&#039;
postal_code: &#039;&#039;
country: &#039;&#039;');
    $assert_session->fieldValueNotEquals('properties[default_value]', '');

    // Check default value is passed set default value form.
    $this->submitForm([], 'Set default value');
    $assert_session->fieldValueEquals('default_value[address]', '{address}');
    $assert_session->fieldValueEquals('default_value[address_2]', '{address_2}');

    // Change back the element edit form.
    $this->submitForm($edit, 'Update default value');
    $assert_session->fieldValueNotEquals('properties[default_value]', '');

    // Check clearing default value.
    $this->submitForm([], 'Clear default value');
    $assert_session->fieldValueEquals('properties[default_value]', '');
  }

}
