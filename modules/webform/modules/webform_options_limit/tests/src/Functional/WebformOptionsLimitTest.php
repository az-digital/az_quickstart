<?php

namespace Drupal\Tests\webform_options_limit\Functional;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Webform options limit test.
 *
 * @group webform_options_limit
 */
class WebformOptionsLimitTest extends WebformBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'webform',
    'webform_options_limit',
    'webform_options_limit_test',
  ];

  /**
   * Test options limit.
   */
  public function testOptionsLimit() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_handler_options_limit');

    $this->drupalGet('/webform/test_handler_options_limit');

    // Check that option None is not available.
    $assert_session->responseContains('<input data-drupal-selector="edit-options-limit-default-none" disabled="disabled" type="checkbox" id="edit-options-limit-default-none" name="options_limit_default[none]" value="none" class="form-checkbox" />');
    $assert_session->responseContains('None [0 remaining]');

    // Check that option A is available.
    $assert_session->responseContains('<input data-drupal-selector="edit-options-limit-default-a" type="checkbox" id="edit-options-limit-default-a" name="options_limit_default[A]" value="A" checked="checked" class="form-checkbox" />');
    $assert_session->responseContains('A [1 remaining]');

    // Check that option None is not available.
    $assert_session->responseContains('<input data-drupal-selector="edit-options-limit-messages-none" aria-describedby="edit-options-limit-messages-none--description" disabled="disabled" type="checkbox" id="edit-options-limit-messages-none" name="options_limit_messages[none]" value="none" class="form-checkbox" />');
    $assert_session->responseContains('No options remaining / 0 limit / 0 total');

    // Check that option D is available.
    $assert_session->responseContains('<input data-drupal-selector="edit-options-limit-messages-d" aria-describedby="edit-options-limit-messages-d--description" type="checkbox" id="edit-options-limit-messages-d" name="options_limit_messages[D]" value="D" checked="checked" class="form-checkbox" />');
    $assert_session->responseContains('1 option remaining / 1 limit / 0 total');

    // Check that option H is available.
    $assert_session->responseContains('<option value="H" selected="selected">H [1 remaining]</option>');

    // Check that option K is available.
    $assert_session->responseContains('<option value="K" selected="selected">K [1 remaining]</option>');

    // Check that option O is available.
    $assert_session->responseContains('<option value="O" selected="selected">O [1 remaining]</option>');

    // Check that table select multiple is available.
    $assert_session->checkboxChecked('options_limit_tableselect_multiple[U]');
    $assert_session->responseContains('<input class="tableselect form-checkbox" data-drupal-selector="edit-options-limit-tableselect-multiple-u" type="checkbox" id="edit-options-limit-tableselect-multiple-u" name="options_limit_tableselect_multiple[U]" value="U" checked="checked" />');
    $assert_session->responseContains('<td>U [1 remaining]</td>');

    // Check that table select single is available.
    $assert_session->checkboxChecked('edit-options-limit-tableselect-single-x');
    $assert_session->responseContains('<input class="tableselect form-radio" data-drupal-selector="edit-options-limit-tableselect-single-x" type="radio" id="edit-options-limit-tableselect-single-x" name="options_limit_tableselect_single" value="X" checked="checked" />');
    $assert_session->responseMatches('#<th>options_limit_tableselect_single</th>\s+<th>Limits</th>#');
    $assert_session->responseContains('<td>X</td>');
    $assert_session->responseContains('<td> [1 remaining]</td>');

    // Post first submission.
    $sid_1 = $this->postSubmission($webform);

    // Check that option A is disabled with 0 remaining.
    $assert_session->responseContains('<input data-drupal-selector="edit-options-limit-default-a" disabled="disabled" type="checkbox" id="edit-options-limit-default-a" name="options_limit_default[A]" value="A" class="form-checkbox" />');
    $assert_session->responseContains('A [0 remaining]');

    // Check that option B is disabled with custom remaining message.
    $assert_session->responseContains('<input data-drupal-selector="edit-options-limit-messages-d" aria-describedby="edit-options-limit-messages-d--description" disabled="disabled" type="checkbox" id="edit-options-limit-messages-d" name="options_limit_messages[D]" value="D" class="form-checkbox" />');
    $assert_session->responseContains('No options remaining / 1 limit / 1 total');

    // Check that option H is no longer selected and disabled via JavaScript.
    $assert_session->responseContains('<option value="H">H [0 remaining]</option>');
    $assert_session->responseContains('data-webform-select-options-disabled="H"');

    // Check that option K was removed.
    $assert_session->responseNotContains('<option value="K"');

    // Check that option O was not changed but is not selected.
    $assert_session->responseContains('<option value="O">O [0 remaining]</option>');

    // Check that table select multiple is NOT available.
    $assert_session->fieldNotExists('edit-options-limit-tableselect-multiple-u');
    $assert_session->responseContains('<td>U [0 remaining]</td>');

    // Check that table select single is available.
    $assert_session->fieldNotExists('edit-options-limit-tableselect-single-x');
    $assert_session->responseContains('<td>X</td>');
    $assert_session->responseContains('<td> [0 remaining]</td>');

    // Check that option O being selected triggers validation error.
    $this->postSubmission($webform, ['options_limit_select_none[]' => 'O']);
    $assert_session->responseContains('options_limit_select_none: O is unavailable.');

    // Chech that unavailable option can't be prepopulated.
    $this->drupalGet('/webform/test_handler_options_limit', ['query' => ['options_limit_default[]' => 'A']]);
    $assert_session->checkboxNotChecked('edit-options-limit-default-a');
    $this->drupalGet('/webform/test_handler_options_limit', ['query' => ['options_limit_default[]' => 'B']]);
    $assert_session->checkboxChecked('edit-options-limit-default-b');

    // Post two more submissions.
    $this->postSubmission($webform);
    $this->postSubmission($webform);

    // Change that 'options_limit_default' is disabled and not available.
    $assert_session->responseContains('A [0 remaining]');
    $assert_session->responseContains('B [0 remaining]');
    $assert_session->responseContains('C [0 remaining]');
    $assert_session->responseContains('options_limit_default is not available.');

    // Login as an admin.
    $this->drupalLogin($this->rootUser);

    // Check that random test values are the only available options.
    $this->drupalGet('/webform/test_handler_options_limit/test');
    $assert_session->responseContains('<option value="J" selected="selected">J [Unlimited]</option>');
    $this->drupalGet('/webform/test_handler_options_limit/test');
    $assert_session->responseContains('<option value="J" selected="selected">J [Unlimited]</option>');
    $this->drupalGet('/webform/test_handler_options_limit/test');
    $assert_session->responseContains('<option value="J" selected="selected">J [Unlimited]</option>');

    // Check that existing submission values are not disabled.
    $this->drupalGet("/admin/structure/webform/manage/test_handler_options_limit/submission/$sid_1/edit");
    $assert_session->responseContains('<input data-drupal-selector="edit-options-limit-default-a" type="checkbox" id="edit-options-limit-default-a" name="options_limit_default[A]" value="A" checked="checked" class="form-checkbox" />');
    $assert_session->responseContains('A [0 remaining]');
    $assert_session->responseContains('<input data-drupal-selector="edit-options-limit-messages-d" aria-describedby="edit-options-limit-messages-d--description" type="checkbox" id="edit-options-limit-messages-d" name="options_limit_messages[D]" value="D" checked="checked" class="form-checkbox" />');
    $assert_session->responseContains('No options remaining / 1 limit / 1 total');
    $assert_session->responseContains('<option value="H" selected="selected">H [0 remaining]</option>');
    $assert_session->responseContains('<option value="K" selected="selected">K [0 remaining]</option>');
    $assert_session->responseContains('<option value="O" selected="selected">O [0 remaining]</option>');

    // Check that Options limit report is available.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/structure/webform/manage/test_handler_options_limit/results/options-limit');
    $assert_session->statusCodeEquals(200);

    // Check handler element error messages.
    $webform->deleteElement('options_limit_default');
    $webform->save();
    $this->drupalGet('/admin/structure/webform/manage/test_handler_options_limit/handlers');
    $assert_session->responseContains('<b class="color-error">\'options_limit_default\' is missing.</b>');
  }

}
