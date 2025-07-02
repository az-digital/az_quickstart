<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform submission form autofill.
 *
 * @group webform
 */
class WebformSettingsAutofillTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_autofill'];

  /**
   * Test webform submission form autofill.
   */
  public function testAutofill() {
    $assert_session = $this->assertSession();

    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);

    $webform = Webform::load('test_form_autofill');

    // Check that elements are empty.
    $this->drupalGet('/webform/test_form_autofill');
    $assert_session->responseNotContains('This submission has been autofilled with your previous submission.');

    // Check that 'textfield_excluded' is empty.
    $assert_session->fieldValueEquals('textfield_excluded', '');

    // Check that 'textfield_autofill' is empty.
    $assert_session->fieldValueEquals('textfield_autofill', '');

    // Check that 'telephone_excluded' is empty.
    $assert_session->fieldValueEquals('telephone_excluded[type]', '');
    $assert_session->fieldValueEquals('telephone_excluded[phone]', '');
    $assert_session->fieldValueEquals('telephone_excluded[ext]', '');

    // Check that 'telephone_autofill' is empty.
    $assert_session->fieldValueEquals('telephone_autofill[type]', '');
    $assert_session->fieldValueEquals('telephone_autofill[phone]', '');
    $assert_session->fieldValueEquals('telephone_autofill[ext]', '');

    // Check that 'telephone_autofill_partial' is empty.
    $assert_session->fieldValueEquals('telephone_autofill_partial[type]', '');
    $assert_session->fieldValueEquals('telephone_autofill_partial[phone]', '');
    $assert_session->fieldValueEquals('telephone_autofill_partial[ext]', '');

    // Check that 'telephone_autofill_partial_multiple' is empty.
    $assert_session->fieldValueEquals('telephone_autofill_partial_multiple[items][0][_item_][type]', '');
    $assert_session->fieldValueEquals('telephone_autofill_partial_multiple[items][0][_item_][phone]', '');
    $assert_session->fieldValueEquals('telephone_autofill_partial_multiple[items][0][_item_][ext]', '');

    // Create a submission.
    $edit = [
      'textfield_excluded' => '{textfield_excluded}',
      'textfield_autofill' => '{textfield_autofill}',
      'telephone_excluded[type]' => 'Cell',
      'telephone_excluded[phone]' => '+1 111-111-1111',
      'telephone_excluded[ext]' => '111',
      'telephone_autofill[type]' => 'Cell',
      'telephone_autofill[phone]' => '+1 222-222-2222',
      'telephone_autofill[ext]' => '222',
      'telephone_autofill_partial[type]' => 'Cell',
      'telephone_autofill_partial[phone]' => '+1 333-333-3333',
      'telephone_autofill_partial[ext]' => '333',
      'telephone_autofill_partial_multiple[items][0][_item_][type]' => 'Cell',
      'telephone_autofill_partial_multiple[items][0][_item_][phone]' => '+1 444-444-4444',
      'telephone_autofill_partial_multiple[items][0][_item_][ext]' => '444',
    ];
    $this->postSubmission($webform, $edit);

    // Get autofilled submission form.
    $this->drupalGet('/webform/test_form_autofill');

    // Check that 'textfield_excluded' is empty.
    $assert_session->fieldValueNotEquals('textfield_excluded', '{textfield_excluded}');
    $assert_session->fieldValueEquals('textfield_excluded', '');

    // Check that 'textfield_autofill' is autofilled.
    $assert_session->fieldValueEquals('textfield_autofill', '{textfield_autofill}');

    // Check that 'telephone_excluded[' is empty.
    $assert_session->fieldValueEquals('telephone_excluded[type]', '');
    $assert_session->fieldValueEquals('telephone_excluded[phone]', '');
    $assert_session->fieldValueEquals('telephone_excluded[ext]', '');

    // Check that 'telephone__autofill' is autofilled.
    $assert_session->fieldValueEquals('telephone_autofill[type]', 'Cell');
    $assert_session->fieldValueEquals('telephone_autofill[phone]', '+1 222-222-2222');
    $assert_session->fieldValueEquals('telephone_autofill[ext]', '222');

    // Check that 'telephone__autofill_partial' is partially autofilled.
    $assert_session->fieldValueEquals('telephone_autofill_partial[type]', 'Cell');
    $assert_session->fieldValueEquals('telephone_autofill_partial[phone]', '');
    $assert_session->fieldValueEquals('telephone_autofill_partial[ext]', '');

    // Check that 'telephone__autofill_partial_multiple' is partially autofilled.
    $assert_session->fieldValueEquals('telephone_autofill_partial_multiple[items][0][_item_][type]', 'Cell');
    $assert_session->fieldValueEquals('telephone_autofill_partial_multiple[items][0][_item_][phone]', '');
    $assert_session->fieldValueEquals('telephone_autofill_partial_multiple[items][0][_item_][ext]', '');

    // Check that default configuration message is displayed.
    $this->drupalGet('/webform/test_form_autofill');
    $assert_session->fieldValueEquals('textfield_autofill', '{textfield_autofill}');
    $assert_session->responseContains('This submission has been autofilled with your previous submission.');

    // Clear default autofill message.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_autofill_message', '')
      ->save();

    // Check no autofill message is displayed.
    $this->drupalGet('/webform/test_form_autofill');
    $assert_session->fieldValueEquals('textfield_autofill', '{textfield_autofill}');
    $assert_session->responseNotContains('This submission has been autofilled with your previous submission.');

    // Set custom autofill message.
    $webform
      ->setSetting('autofill_message', '{autofill_message}')
      ->save();

    // Check custom autofill message is displayed.
    $this->drupalGet('/webform/test_form_autofill');
    $assert_session->fieldValueEquals('textfield_autofill', '{textfield_autofill}');
    $assert_session->responseContains('{autofill_message}');
  }

}
