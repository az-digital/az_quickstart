<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for mapping element.
 *
 * @group webform
 */
class WebformElementMappingTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_mapping'];

  /**
   * Test mapping element.
   */
  public function testMappingElement() {
    $assert_session = $this->assertSession();

    $this->drupalGet('/webform/test_element_mapping');

    // Check default element.
    $assert_session->responseContains('<th>Source &rarr;</th>');
    $assert_session->responseContains('<th>Destination</th>');
    $assert_session->responseContains('<select data-drupal-selector="edit-webform-mapping-one" id="edit-webform-mapping-one" name="webform_mapping[one]" class="form-select"><option value="" selected="selected">- Select -</option><option value="four">Four</option><option value="five">Five</option><option value="six">Six</option></select>');

    // Check source description.
    $assert_session->responseContains('<td>One &rarr;<div class="description js-form-wrapper form-wrapper" data-drupal-selector="edit-table-one-source-data-description" id="edit-table-one-source-data-description">This is a description. This is a <a href="https://google.com">link</a></div>');

    // Check source help.
    $assert_session->responseContains('<td>One<span data-drupal-selector="edit-table-one-source-data-help" class="webform-element-help js-webform-element-help" role="tooltip" tabindex="0" aria-label="One" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;One&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;This is help. This is a &lt;a href=&quot;https://google.com&quot;&gt;link&lt;/a&gt;&lt;/div&gt;"><span aria-hidden="true">?</span></span> &rarr;</td>');

    // Check custom element.
    $assert_session->responseContains('<th>{Custom source} &raquo;</th>');
    $assert_session->responseContains('<th>{Destination source}</th>');
    $assert_session->responseContains('<select data-drupal-selector="edit-webform-mapping-one" id="edit-webform-mapping-one" name="webform_mapping[one]" class="form-select"><option value="" selected="selected">- Select -</option><option value="four">Four</option><option value="five">Five</option><option value="six">Six</option></select>');

    // Check custom select other element type.
    $assert_session->responseContains('<input data-drupal-selector="edit-webform-mapping-select-other-one-other" type="text" id="edit-webform-mapping-select-other-one-other" name="webform_mapping_select_other[one][other]" value="" size="60" maxlength="255" placeholder="Enter other…" class="form-text" />');

    // Check custom textfield #size property.
    $assert_session->responseContains('<input data-drupal-selector="edit-webform-mapping-textfield-one" type="text" id="edit-webform-mapping-textfield-one" name="webform_mapping_textfield[one]" value="" size="10" maxlength="128" class="form-text" />');

    // Check required.
    $this->drupalGet('/webform/test_element_mapping');
    $this->submitForm([], 'Submit');
    $assert_session->responseContains('webform_mapping_required field is required.');
    $assert_session->responseContains('One field is required.');
    $assert_session->responseContains('Two field is required.');
    $assert_session->responseContains('Three field is required.');

    // Check that required all element does not display error since all the
    // destination elements are required.
    // @see \Drupal\webform\Element\WebformMapping::validateWebformMapping
    $assert_session->responseNotContains('webform_mapping_required_all field is required.');

    // Check processing.
    $edit = [
      'webform_mapping[one]' => 'four',
      'webform_mapping[three]' => 'six',
      'webform_mapping_description[two]' => 'five',
      'webform_mapping_help[two]' => 'five',
      'webform_mapping_required[one]' => 'four',
      'webform_mapping_required_all[one]' => 'four',
      'webform_mapping_required_all[two]' => 'five',
      'webform_mapping_required_all[three]' => 'six',
      'webform_mapping_custom[Sunday]' => 'four',
      'webform_mapping_custom[Monday]' => 'four',
      'webform_mapping_custom[Tuesday]' => 'four',
      'webform_mapping_custom[Wednesday]' => 'four',
      'webform_mapping_custom[Thursday]' => 'four',
      'webform_mapping_custom[Friday]' => 'four',
      'webform_mapping_custom[Saturday]' => 'four',
      'webform_mapping_select_other[one][select]' => 'five',
      'webform_mapping_select_other[two][select]' => 'five',
      'webform_mapping_select_other[three][select]' => '_other_',
      'webform_mapping_select_other[three][other]' => '{other}',
      'webform_mapping_textfield[one]' => 'Loremipsum',
      'webform_mapping_textfield[two]' => 'Loremipsum',
      'webform_mapping_textfield[three]' => 'Loremipsum',
      'webform_mapping_email_multiple[one]' => 'example@example.com, test@test.com, random@random.com',
      'webform_mapping_email_multiple[two]' => '',
      'webform_mapping_email_multiple[three]' => '',
    ];

    // Check preview.
    $this->drupalGet('/webform/test_element_mapping');
    $this->submitForm($edit, 'Preview');

    // Check that source description is not displayed.
    $assert_session->responseContains('<li>Two &rarr; Five</li>');

    // Check submitted values.
    $this->drupalGet('/webform/test_element_mapping');
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains("webform_mapping:
  one: four
  three: six
webform_mapping_description:
  two: five
webform_mapping_help:
  two: five
webform_mapping_custom:
  Sunday: four
  Monday: four
  Tuesday: four
  Wednesday: four
  Thursday: four
  Friday: four
  Saturday: four
webform_mapping_required:
  one: four
webform_mapping_required_all:
  one: four
  two: five
  three: six
webform_mapping_select_other:
  one: five
  two: five
  three: '{other}'
webform_mapping_textfield:
  one: Loremipsum
  two: Loremipsum
  three: Loremipsum
webform_mapping_email_multiple:
  one: 'example@example.com, test@test.com, random@random.com'");
  }

}
