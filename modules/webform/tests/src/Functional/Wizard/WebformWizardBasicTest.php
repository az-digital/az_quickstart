<?php

namespace Drupal\Tests\webform\Functional\Wizard;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform basic wizard.
 *
 * @group webform
 */
class WebformWizardBasicTest extends WebformWizardTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_wizard_basic'];

  /**
   * Test webform basic wizard.
   */
  public function testBasicWizard() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    // Create a wizard submission.
    $wizard_webform = Webform::load('test_form_wizard_basic');
    $this->drupalGet('/webform/test_form_wizard_basic');
    $this->submitForm([], 'Next >');
    $this->submitForm([], 'Submit');
    $sid = $this->getLastSubmissionId($wizard_webform);

    // Check confirmation message for wizard form.
    $this->drupalGet("admin/structure/webform/manage/test_form_wizard_basic/submission/$sid/edit");
    $this->assertCurrentPage('Page 1', 'page_1');
    $this->submitForm([], 'Next >');
    $this->assertCurrentPage('Page 2', 'page_2');
    $this->submitForm([], 'Save');
    $assert_session->responseContains('Submission updated in <em class="placeholder">Test: Webform: Wizard basic</em>.');
    $this->assertCurrentPage('Page 1', 'page_1');

    // Check access to 'Edit: All' tab for wizard.
    $this->drupalGet("admin/structure/webform/manage/test_form_wizard_basic/submission/$sid/edit/all");
    $assert_session->statusCodeEquals(200);

    // Check that page 1 and 2 are displayed.
    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.3',
      currentCallable: fn() => $assert_session->responseContains('<summary role="button" aria-controls="edit-page-1" aria-expanded="false">Page 1</summary>'),
      deprecatedCallable: fn() => $assert_session->responseContains('<summary role="button" aria-controls="edit-page-1" aria-expanded="false" aria-pressed="false">Page 1</summary>'),
    );
    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.3',
      currentCallable: fn() => $assert_session->responseContains('<summary role="button" aria-controls="edit-page-2" aria-expanded="false">Page 2</summary>'),
      deprecatedCallable: fn() => $assert_session->responseContains('<summary role="button" aria-controls="edit-page-2" aria-expanded="false" aria-pressed="false">Page 2</summary>'),
    );

    // Create a contact form submission.
    $contact_webform = Webform::load('contact');
    $sid = $this->postSubmissionTest($contact_webform);

    // Check access denied to 'Edit: All' tab for simple form.
    $this->drupalGet("admin/structure/webform/manage/contact/submission/$sid/edit/all");
    $assert_session->statusCodeEquals(403);

    // Enable tracking by name.
    $wizard_webform
      ->setSetting('wizard_track', 'name')
      ->setSetting('confirmation_type', 'inline')
      ->save();

    // Check next page.
    $this->drupalGet('/webform/test_form_wizard_basic');
    $assert_session->responseNotContains('data-webform-wizard-current-page');
    $assert_session->responseContains('data-webform-wizard-page="page_2" data-drupal-selector="edit-wizard-next"');

    // Check next and previous page.
    $this->drupalGet('/webform/test_form_wizard_basic');
    $this->submitForm([], 'Next >');
    $assert_session->responseContains('data-webform-wizard-current-page="page_2"');
    $assert_session->responseContains('data-webform-wizard-page="page_1" data-drupal-selector="edit-wizard-prev"');
    $assert_session->responseContains('data-webform-wizard-page="webform_preview" data-drupal-selector="edit-preview-next"');

    $this->submitForm([], 'Preview');
    $assert_session->responseContains('data-webform-wizard-current-page="webform_preview"');
    $assert_session->responseContains('data-webform-wizard-page="page_2" data-drupal-selector="edit-preview-prev"');
    $assert_session->responseContains('data-webform-wizard-page="webform_confirmation" data-drupal-selector="edit-submit"');

    $this->submitForm([], 'Submit');
    $assert_session->responseContains('data-webform-wizard-current-page="webform_confirmation"');

    // Enable tracking by index.
    $wizard_webform->setSetting('wizard_track', 'index')->save();

    // Check next page.
    $this->drupalGet('/webform/test_form_wizard_basic');
    $assert_session->responseContains('data-webform-wizard-page="2" data-drupal-selector="edit-wizard-next"');

    // Check next and previous page.
    $this->drupalGet('/webform/test_form_wizard_basic');
    $this->submitForm([], 'Next >');
    $assert_session->responseContains('data-webform-wizard-current-page="2"');
    $assert_session->responseContains('data-webform-wizard-page="1" data-drupal-selector="edit-wizard-prev"');
    $assert_session->responseContains('data-webform-wizard-page="3" data-drupal-selector="edit-preview-next"');

    $this->submitForm([], 'Preview');
    $assert_session->responseContains('data-webform-wizard-current-page="3"');
    $assert_session->responseContains('data-webform-wizard-page="2" data-drupal-selector="edit-preview-prev"');
    $assert_session->responseContains('data-webform-wizard-page="4" data-drupal-selector="edit-submit"');

    $this->submitForm([], 'Submit');
    $assert_session->responseContains('data-webform-wizard-current-page="4"');
  }

}
