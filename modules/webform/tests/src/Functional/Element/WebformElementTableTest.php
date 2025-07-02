<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\webform\Entity\Webform;

/**
 * Tests for table elements.
 *
 * @group webform
 */
class WebformElementTableTest extends WebformElementBrowserTestBase {


  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_ui', 'file'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_table'];

  /**
   * Tests table elements.
   */
  public function testTable() {
    global $base_path;

    $assert_session = $this->assertSession();

    $webform = Webform::load('test_element_table');

    $this->drupalGet('/webform/test_element_table');

    /* ********************************************************************** */
    // Rendering.
    /* ********************************************************************** */

    // Check default table rendering.
    $assert_session->responseContains('<table class="js-form-wrapper responsive-enabled" data-drupal-selector="edit-table" id="edit-table" data-striping="1">');
    $assert_session->responseMatches('#<th>First Name</th>\s+<th>Last Name</th>\s+<th>Gender</th>#');
    $assert_session->responseContains('<tr data-drupal-selector="edit-table-1">');
    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.2',
      currentCallable: fn() => $assert_session->responseContains('<td><div class="js-form-item form-item form-type-textfield js-form-type-textfield form-item-table__1__first-name js-form-item-table__1__first-name form-no-label">'),
      deprecatedCallable: fn() => $assert_session->responseContains('<td><div class="js-form-item form-item js-form-type-textfield form-item-table__1__first-name js-form-item-table__1__first-name form-no-label">'),
    );
    $assert_session->responseContains('<input data-drupal-selector="edit-table-1-first-name" type="text" id="edit-table-1-first-name" name="table__1__first_name" value="John" size="20" maxlength="255" class="form-text" />');

    // Check webform table basic rendering.
    $assert_session->responseContains('<table data-drupal-selector="edit-table-basic" class="webform-table responsive-enabled" id="edit-table-basic" data-striping="1">');
    $assert_session->responseContains('<tr data-drupal-selector="edit-table-basic-01" class="webform-table-row">');
    $assert_session->responseContains('<input data-drupal-selector="edit-table-basic-01-first-name" type="text" id="edit-table-basic-01-first-name" name="table_basic_01_first_name" value="" size="60" maxlength="255" class="form-text" />');

    // Check webform table advanced rendering.
    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.3',
      currentCallable: fn() => $assert_session->responseContains('<table data-drupal-selector="edit-table-advanced" class="webform-table sticky-header responsive-enabled" id="edit-table-advanced" data-striping="1">'),
      deprecatedCallable: fn() => $assert_session->responseContains('<table data-drupal-selector="edit-table-advanced" class="webform-table sticky-enabled responsive-enabled" id="edit-table-advanced" data-striping="1">'),
    );
    $assert_session->responseMatches('#<th width="50%">Composite</th>\s+<th width="50%">Nested</th>#');

    // Check webform table states rendering.
    $assert_session->responseContains('<table data-drupal-selector="edit-table-states" class="webform-table responsive-enabled" id="edit-table-states" data-drupal-states="{&quot;invisible&quot;:{&quot;.webform-submission-test-element-table-add-form :input[name=\u0022table_rows\u0022]&quot;:{&quot;value&quot;:&quot;&quot;}}}" data-striping="1">');
    $assert_session->responseContains('<tr data-drupal-selector="edit-table-states-01" class="webform-table-row js-form-item" data-drupal-states="{&quot;visible&quot;:{&quot;.webform-submission-test-element-table-add-form :input[name=\u0022table_rows\u0022]&quot;:{&quot;value&quot;:{&quot;greater&quot;:&quot;0&quot;}}}}">');

    /* ********************************************************************** */
    // Display.
    /* ********************************************************************** */

    $this->drupalGet('/webform/test_element_table');
    $edit = [
      'table_basic_01_first_name' => 'Ringo',
      'table_basic_01_last_name' => 'Starr',
      'table_basic_01_gender' => 'Man',
      'table_advanced_01_first_name' => 'John',
      'table_advanced_01_last_name' => 'Lennon',
      'table_advanced_01_gender' => 'Man',
    ];
    $this->submitForm($edit, 'Preview');

    // Check data.
    $assert_session->responseContains("table__1__first_name: John
table__1__last_name: Smith
table__1__gender: Man
table__2__first_name: Jane
table__2__last_name: Doe
table__2__gender: Woman
table_basic_01_first_name: Ringo
table_basic_01_last_name: Starr
table_basic_01_gender: Man
table_advanced_01_address: null
table_advanced_01_first_name: John
table_advanced_01_last_name: Lennon
table_advanced_01_gender: Man
table_advanced_01_managed_file: null
table_rows: '1'
table_advanced_01_textfield: ''
table_advanced_02_textfield: ''
table_advanced_03_textfield: ''
table_advanced_04_textfield: ''");

    // Check default table display.
    $assert_session->responseMatches('#<th>First Name</th>\s+<th>Last Name</th>\s+<th>Gender</th>\s+<th>Markup</th>#');
    $assert_session->responseMatches('#<td>John</td>\s+<td>Smith</td>\s+<td>Man</td>\s+<td>{markup_1}</td>#');
    $assert_session->responseMatches('#<td>Jane</td>\s+<td>Doe</td>\s+<td>Woman</td>\s+<td>{markup_2}</td>#');

    // Check basic table display.
    $assert_session->responseMatches('#<label>table_basic</label>\s+<table class="responsive-enabled" data-striping="1">#');
    $assert_session->responseMatches('#<tr>\s+<td>Ringo</td>\s+<td>Starr</td>\s+<td>Man</td>\s+<td>{markup_1}</td>\s+</tr>#');

    // Check advanced table display.
    $assert_session->responseMatches('#<label>table_advanced</label>\s+<div><details class="webform-container webform-container-type-details#');
    $assert_session->responseContains('<section class="js-form-item form-item js-form-wrapper form-wrapper webform-section" id="test_element_table--table_advanced_01_container">');

    // Check states table display.
    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.2',
      currentCallable: fn() => $assert_session->responseMatches('<div class="webform-element webform-element-type-webform-table js-form-item form-item form-type-item js-form-type-item form-item-table-states js-form-item-table-states" id="test_element_table--table_states">'),
      deprecatedCallable: fn() => $assert_session->responseMatches('<div class="webform-element webform-element-type-webform-table js-form-item form-item js-form-type-item form-item-table-states js-form-item-table-states" id="test_element_table--table_states">'),
    );

    /* ********************************************************************** */
    // User interface.
    /* ********************************************************************** */

    $this->drupalLogin($this->rootUser);

    // Check that add table row is not displayed in select element.
    $this->drupalGet('/admin/structure/webform/manage/test_element_table/element/add');
    $assert_session->responseNotContains('Table row');

    // Check that add table row link is displayed.
    $this->drupalGet('/admin/structure/webform/manage/test_element_table');
    $assert_session->linkByHrefExists("{$base_path}admin/structure/webform/manage/test_element_table/element/add/webform_table_row?parent=table_basic");

    // Check that add table row without a parent table returns a 404 error.
    $this->drupalGet('/admin/structure/webform/manage/test_element_table/element/add/webform_table_row');
    $assert_session->statusCodeEquals(404);

    // Check default table row element key and title.
    $this->drupalGet('/admin/structure/webform/manage/test_element_table/element/add/webform_table_row', ['query' => ['parent' => 'table_basic']]);
    $assert_session->fieldValueEquals('properties[title]', 'Basic Person (2)');
    $assert_session->fieldValueEquals('key', 'table_basic_02');

    // Check table row element can duplicate sub elements from the
    // first table row.
    $options = ['query' => ['parent' => 'table_basic']];
    $this->drupalGet('/admin/structure/webform/manage/test_element_table/element/add/webform_table_row', $options);
    $assert_session->checkboxChecked('properties[duplicate]');

    // Check table row element sub elements are duplicated.
    $this->drupalGet('/admin/structure/webform/manage/test_element_table/element/add/webform_table_row', $options);
    $this->submitForm([], 'Save');
    $assert_session->responseContains('>table_basic_02<');
    $assert_session->responseContains('>table_basic_02_first_name<');
    $assert_session->responseContains('>table_basic_02_last_name<');
    $assert_session->responseContains('>table_basic_02_gender<');
    $assert_session->responseContains('>table_basic_02_markup<');

    // Check table row element sub elements are NOT duplicated.
    $this->drupalGet('/admin/structure/webform/manage/test_element_table/element/add/webform_table_row', $options);
    $edit = ['properties[duplicate]' => FALSE];
    $this->submitForm($edit, 'Save');
    $assert_session->responseContains('>table_basic_03<');
    $assert_session->responseNotContains('>table_basic_03_first_name<');
    $assert_session->responseNotContains('>table_basic_03_last_name<');
    $assert_session->responseNotContains('>table_basic_03_gender<');
    $assert_session->responseNotContains('>table_basic_03_markup<');

    // Check default table row element key and title.
    $this->drupalGet('/admin/structure/webform/manage/test_element_table/element/add/textfield', ['query' => ['parent' => 'table_basic_01']]);
    $assert_session->responseContains("Element keys are automatically prefixed with parent row's key.");
    $assert_session->responseContains('<span class="field-prefix">table_basic_01_</span>');

    // Check that elements are prefixed with row key.
    $options = ['query' => ['parent' => 'table_basic_01']];
    $this->drupalGet('/admin/structure/webform/manage/test_element_table/element/add/textfield', $options);
    $edit = ['key' => 'testing', 'properties[title]' => 'Testing'];
    $this->submitForm($edit, 'Save');
    $assert_session->responseContains('>table_basic_01_testing<');

    // Check table row element can NOT duplicate sub elements from the
    // first table row.
    $webform->setElementProperties('textfield', [
      '#type' => 'textfield',
      'title' => 'textfield',
    ], 'table_basic_01')->save();
    $this->drupalGet('/admin/structure/webform/manage/test_element_table/element/add/webform_table_row', ['query' => ['parent' => 'table_basic']]);
    $assert_session->fieldNotExists('properties[duplicate]');

    // Check prefix children disabled for table row.
    $this->drupalGet('/admin/structure/webform/manage/test_element_table/element/add/webform_table_row', ['query' => ['parent' => 'table_prefix_children_false']]);
    $assert_session->fieldValueEquals('properties[title]', '');
    $assert_session->fieldValueEquals('key', '');

    // Check prefix children disabled for table row element.
    $this->drupalGet('/admin/structure/webform/manage/test_element_table/element/add/textfield', ['query' => ['parent' => 'table_prefix_children_false_01']]);
    $assert_session->responseNotContains("Element keys are automatically prefixed with parent row's key.");
    $assert_session->responseNotContains('<span class="field-prefix">table_basic_01_</span>');
  }

}
