<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for webform element #states.
 *
 * @group webform
 */
class WebformElementStatesTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_states'];

  /**
   * Tests element #states.
   */
  public function testElement() {
    $assert_session = $this->assertSession();

    /* ********************************************************************** */
    // Processing.
    /* ********************************************************************** */

    // Check default value handling.
    $this->drupalGet('/webform/test_element_states');
    $this->submitForm([], 'Submit');

    $this->assertWebformYaml("states_basic:
  enabled:
    selector_01:
      checked: true
  required:
    'selector_01''':
      checked: true
    selector_02:
      checked: true
  disabled:
    - selector_01:
        checked: true
    - or
    - selector_02:
        checked: true
states_values:
  enabled:
    selector_01:
      value: '0'
    selector_02:
      value: 'false'
    selector_03:
      value: ''
    selector_04:
      checked: true
states_custom_selector:
  required:
    custom_selector:
      value: 'Yes'
states_empty: {  }
states_single: {  }
states_unsupported_operator:
  required:
    - custom_selector:
        value: 'Yes'
    - xxx
    - custom_selector:
        value: 'Yes'
states_unsupported_nesting:
  required:
    - selector_01:
        value: 'Yes'
      selector_02:
        value: 'Yes'
    - or
    - selector_03:
        value: 'Yes'
      selector_04:
        value: 'Yes'
states_custom_condition:
  required:
    custom_selector:
      value:
        pattern: '[a-z0-9]+'");

    /* ********************************************************************** */
    // Rendering.
    /* ********************************************************************** */

    $this->drupalGet('/webform/test_element_states');

    // Check 'States custom selector'.
    $assert_session->responseContains('<option value="custom_selector" selected="selected">custom_selector</option>');

    // Check 'States unsupport operator'.
    $assert_session->responseContains('Conditional logic (Form API #states) is using the <em class="placeholder">XXX</em> operator. Form API #states must be manually entered.');
    $assert_session->responseContains('<textarea data-drupal-selector="edit-states-unsupported-operator-states" aria-describedby="edit-states-unsupported-operator-states--description" class="js-webform-codemirror webform-codemirror yaml form-textarea" data-webform-codemirror-mode="text/x-yaml" id="edit-states-unsupported-operator-states" name="states_unsupported_operator[states]" rows="5" cols="60">');

    // Check 'States unsupport nested multiple selectors'.
    $assert_session->responseContains('Conditional logic (Form API #states) is using multiple nested conditions. Form API #states must be manually entered.');
    $assert_session->responseContains('<textarea data-drupal-selector="edit-states-unsupported-nesting-states" aria-describedby="edit-states-unsupported-nesting-states--description" class="js-webform-codemirror webform-codemirror yaml form-textarea" data-webform-codemirror-mode="text/x-yaml" id="edit-states-unsupported-nesting-states" name="states_unsupported_nesting[states]" rows="5" cols="60">');

    // Check 'States single' (#multiple: FALSE)
    $assert_session->buttonExists('edit-states-empty-actions-add');
    $assert_session->buttonNotExists('edit-states-single-actions-add');

    /* ********************************************************************** */
    // Validation.
    /* ********************************************************************** */

    // Check duplicate states validation.
    $this->drupalGet('/webform/test_element_states');
    $edit = ['states_basic[states][0][state]' => 'required'];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('The <em class="placeholder">Required</em> state is declared more than once. There can only be one declaration per state.');

    // Check duplicate selectors validation.
    $this->drupalGet('/webform/test_element_states');
    $edit = ['states_basic[states][3][selector]' => 'selector_02'];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('The <em class="placeholder">Selector 02 (selector_02)</em> element is used more than once within the <em class="placeholder">Required</em> state. To use multiple values within a trigger try using the pattern trigger.');

    /* ********************************************************************** */
    // Processing.
    /* ********************************************************************** */

    // Check setting first state and adding new state.
    $edit = [
      'states_empty[states][0][state]' => 'required',
      'states_empty[states][1][selector]' => 'selector_01',
      'states_empty[states][1][trigger]' => 'value',
      'states_empty[states][1][value]' => '{value_01}',
    ];
    $this->submitForm($edit, 'states_empty_table_add');

    // Check the first state/condition is required and value = {value_01}.
    $assert_session->fieldValueEquals('states_empty[states][0][state]', 'required');
    $assert_session->fieldValueEquals('states_empty[states][1][selector]', 'selector_01');
    $assert_session->fieldValueEquals('states_empty[states][1][trigger]', 'value');
    $assert_session->fieldValueEquals('states_empty[states][1][value]', '{value_01}');

    // Check empty second state/condition.
    $assert_session->fieldValueEquals('states_empty[states][2][state]', '');
    $assert_session->fieldValueEquals('states_empty[states][3][selector]', '');
    $assert_session->fieldValueEquals('states_empty[states][3][trigger]', '');
    $assert_session->fieldValueEquals('states_empty[states][3][value]', '');

    $edit = [
      'states_empty[states][2][state]' => 'disabled',
      'states_empty[states][3][selector]' => 'selector_02',
      'states_empty[states][3][trigger]' => 'value',
      'states_empty[states][3][value]' => '{value_02}',
    ];
    $this->submitForm($edit, 'states_empty_table_remove_1');

    // Check the first condition is removed.
    $assert_session->fieldNotExists('states_empty[states][1][selector]');
    $assert_session->fieldNotExists('states_empty[states][1][trigger]');
    $assert_session->fieldNotExists('states_empty[states][1][value]');

    // Check the second state/condition is required and value = {value_01}.
    $assert_session->fieldValueEquals('states_empty[states][1][state]', 'disabled');
    $assert_session->fieldValueEquals('states_empty[states][2][selector]', 'selector_02');
    $assert_session->fieldValueEquals('states_empty[states][2][trigger]', 'value');
    $assert_session->fieldValueEquals('states_empty[states][2][value]', '{value_02}');

    // Remove state two.
    $this->submitForm([], 'states_empty_table_remove_1');

    // Check the second state/condition is removed.
    $assert_session->fieldNotExists('states_empty[states][1][state]');
    $assert_session->fieldNotExists('states_empty[states][2][selector]');
    $assert_session->fieldNotExists('states_empty[states][2][trigger]');
    $assert_session->fieldNotExists('states_empty[states][2][value]');

    /* ********************************************************************** */
    // Edit source.
    /* ********************************************************************** */

    // Check that  'Edit source' button is not available.
    $this->drupalGet('/webform/test_element_states');
    $assert_session->responseNotContains('<input class="button button--danger js-form-submit form-submit" data-drupal-selector="edit-states-basic-actions-source" formnovalidate="formnovalidate" type="submit" id="edit-states-basic-actions-source" name="states_basic_table_source" value="Edit source" />');

    // Check that  'Edit source' button is available.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/webform/test_element_states');
    $assert_session->responseContains('<input class="button button--danger js-form-submit form-submit" data-drupal-selector="edit-states-basic-actions-source" formnovalidate="formnovalidate" type="submit" id="edit-states-basic-actions-source" name="states_basic_table_source" value="Edit source" />');
    $assert_session->fieldNotExists('states_basic[states]');

    // Check that 'source' is editable.
    $this->submitForm([], 'states_basic_table_source');
    $assert_session->responseContains('Creating custom conditional logic (Form API #states) with nested conditions or custom selectors will disable the conditional logic builder. This will require that Form API #states be manually entered.');
    $assert_session->fieldExists('states_basic[states]');
  }

}
