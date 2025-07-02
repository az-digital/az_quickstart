<?php

namespace Drupal\Tests\webform\Functional\Wizard;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform wizard with access controls for pages.
 *
 * @group webform
 */
class WebformWizardAccessTest extends WebformWizardTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_wizard_access'];

  /**
   * Test webform custom wizard.
   */
  public function testConditionalWizard() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_form_wizard_access');

    // Check anonymous user can access 'All' and 'Anonymous' form pages.
    $this->drupalGet('/webform/test_form_wizard_access');
    $assert_session->responseContains('<b class="webform-progress-bar__page-title">All</b>');
    $assert_session->responseContains('<b class="webform-progress-bar__page-title">Anonymous</b>');
    $assert_session->responseNotContains('<b class="webform-progress-bar__page-title">Authenticated</b>');
    $assert_session->responseNotContains('<b class="webform-progress-bar__page-title">Private</b>');

    // Generate an anonymous submission.
    $this->drupalGet('/webform/test_form_wizard_access');
    $this->submitForm([], 'Next >');
    $this->submitForm([], 'Submit');
    $sid = $this->getLastSubmissionId($webform);

    // Check anonymous user can only view 'All' and 'Anonymous' submission data.
    $this->drupalGet("webform/test_form_wizard_access/submissions/$sid");
    $assert_session->responseContains('test_form_wizard_access--page_all');
    $assert_session->responseContains('test_form_wizard_access--page_anonymous');
    $assert_session->responseNotContains('test_form_wizard_access--page_authenticated');
    $assert_session->responseNotContains('test_form_wizard_access--page_private');

    // Check anonymous user can only update 'All' and 'Anonymous' submission data.
    $this->drupalGet("webform/test_form_wizard_access/submissions/$sid/edit");
    $assert_session->responseContains('<b class="webform-progress-bar__page-title">All</b>');
    $assert_session->responseContains('<b class="webform-progress-bar__page-title">Anonymous</b>');
    $assert_session->responseNotContains('<b class="webform-progress-bar__page-title">Authenticated</b>');
    $assert_session->responseNotContains('<b class="webform-progress-bar__page-title">Private</b>');

    // Login authenticated user.
    $this->drupalLogin($this->rootUser);

    // Check authenticated user can access 'All', 'Authenticated', and 'Private' form pages.
    $this->drupalGet('/webform/test_form_wizard_access');
    $assert_session->responseContains('<b class="webform-progress-bar__page-title">All</b>');
    $assert_session->responseNotContains('<b class="webform-progress-bar__page-title">Anonymous</b>');
    $assert_session->responseContains('<b class="webform-progress-bar__page-title">Authenticated</b>');
    $assert_session->responseContains('<b class="webform-progress-bar__page-title">Private</b>');

    // Generate an authenticated submission.
    $this->drupalGet('/webform/test_form_wizard_access');
    $this->submitForm([], 'Next >');
    $this->submitForm([], 'Next >');
    $this->submitForm([], 'Submit');
    $sid = $this->getLastSubmissionId($webform);

    // Check authenticated user can view 'All', 'Authenticated', and 'Private' form pages.
    $this->drupalGet("webform/test_form_wizard_access/submissions/$sid");
    $assert_session->responseContains('test_form_wizard_access--page_all');
    $assert_session->responseNotContains('test_form_wizard_access--page_anonymous');
    $assert_session->responseContains('test_form_wizard_access--page_authenticated');
    $assert_session->responseContains('test_form_wizard_access--page_private');

    // Check anonymous user can only update 'All' and 'Anonymous' submission data.
    $this->drupalGet("webform/test_form_wizard_access/submissions/$sid/edit");
    $assert_session->responseContains('<b class="webform-progress-bar__page-title">All</b>');
    $assert_session->responseNotContains('<b class="webform-progress-bar__page-title">Anonymous</b>');
    $assert_session->responseContains('<b class="webform-progress-bar__page-title">Authenticated</b>');
    $assert_session->responseContains('<b class="webform-progress-bar__page-title">Private</b>');
  }

}
