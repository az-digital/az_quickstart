<?php

namespace Drupal\Tests\webform\Functional\Wizard;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformInterface;

/**
 * Tests for webform advanced wizard.
 *
 * @group webform
 */
class WebformWizardAdvancedTest extends WebformWizardTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_wizard_advanced'];

  /**
   * Test webform advanced wizard.
   */
  public function testAdvancedWizard() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_form_wizard_advanced');

    // Get initial wizard start page (Your Information).
    $this->drupalGet('/webform/test_form_wizard_advanced');
    // Check current page is set to 'Your Information'.
    $this->assertCurrentPage('Your Information', 'information');
    // Check progress pages.
    $assert_session->responseContains('1 of 5');
    // Check progress percentage.
    $assert_session->pageTextContains('(0%)');
    // Check draft button does not exist.
    $assert_session->buttonNotExists('Save Draft');
    // Check next button does exist.
    $assert_session->buttonExists('edit-wizard-next');
    // Check first name field does exist.
    $assert_session->fieldValueEquals('edit-first-name', 'John');
    // Check page container type is section.
    $assert_session->responseContains('<section data-webform-key="information" data-drupal-selector="edit-information" id="edit-information" class="js-form-wrapper form-wrapper js-form-item form-item webform-section">');

    // Create a login user who can save drafts.
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);

    // Move to next page (Contact Information).
    $this->drupalGet('/webform/test_form_wizard_advanced');
    $edit = ['first_name' => 'Jane'];
    $this->submitForm($edit, 'Next >');
    // Check progress bar is set to 'Contact Information'.
    $assert_session->responseMatches('#<li data-webform-page="information" class="webform-progress-bar__page webform-progress-bar__page--done"><b class="webform-progress-bar__page-title">Your Information</b><span></span></li>#');
    $assert_session->responseMatches('#<li data-webform-page="contact" class="webform-progress-bar__page webform-progress-bar__page--current"><b class="webform-progress-bar__page-title">Contact Information</b></li>#');
    // Check progress pages.
    $assert_session->responseContains('2 of 5');
    // Check progress percentage.
    $assert_session->pageTextContains('(25%)');

    // Check draft button does exist.
    $assert_session->buttonExists('edit-draft');
    // Check previous button does exist.
    $assert_session->buttonExists('edit-wizard-prev');
    // Check next button does exist.
    $assert_session->buttonExists('edit-wizard-next');
    // Check email field does exist.
    $assert_session->fieldValueEquals('edit-email', 'johnsmith@example.com');

    // Move to previous page (Your Information) while posting data new data
    // via autosave.
    $edit = ['email' => 'janesmith@example.com'];
    $this->submitForm($edit, '< Previous');
    // Check progress bar is set to 'Your Information'.
    $assert_session->responseMatches('#<li data-webform-page="information" class="webform-progress-bar__page webform-progress-bar__page--current"><b class="webform-progress-bar__page-title">Your Information</b><span></span></li>#');
    // Check nosave class.
    $assert_session->responseContains('js-webform-unsaved');
    // Check no nosave attributes.
    $assert_session->responseNotContains('data-webform-unsaved');
    // Check progress pages.
    $assert_session->responseContains('1 of 5');
    // Check progress percentage.
    $assert_session->pageTextContains('(0%)');

    // Check first name set to Jane.
    $assert_session->fieldValueEquals('edit-first-name', 'Jane');
    // Check sex is still set to Male.
    $assert_session->checkboxChecked('edit-sex-male');

    // Change sex from Male to Female.
    $edit = ['sex' => 'Female'];
    $this->submitForm($edit, 'Save Draft');
    // Check first name set to Jane.
    $assert_session->fieldValueEquals('edit-first-name', 'Jane');
    // Check sex is now set to Female.
    $assert_session->checkboxChecked('edit-sex-female');

    // Move to next page (Contact Information).
    $this->drupalGet('/webform/test_form_wizard_advanced');
    $this->submitForm([], 'Next >');
    // Check nosave class.
    $assert_session->responseContains('js-webform-unsaved');
    // Check nosave attributes.
    $assert_session->responseContains('data-webform-unsaved');
    // Check progress bar is set to 'Contact Information'.
    $this->assertCurrentPage('Contact Information', 'contact');
    // Check progress pages.
    $assert_session->responseContains('2 of 5');
    // Check progress percentage.
    $assert_session->pageTextContains('(25%)');

    // Check email field is now janesmith@example.com.
    $assert_session->fieldValueEquals('edit-email', 'janesmith@example.com');

    // Save draft which saves the 'current_page'.
    $edit = ['phone' => '111-111-1111'];
    $this->submitForm($edit, 'Save Draft');
    // Complete reload the webform.
    $this->drupalGet('/webform/test_form_wizard_advanced');
    // Check progress bar is still set to 'Contact Information'.
    $this->assertCurrentPage('Contact Information', 'contact');

    // Move to last page (Your Feedback).
    $this->submitForm([], 'Next >');
    // Check progress bar is set to 'Your Feedback'.
    $this->assertCurrentPage('Your Feedback', 'feedback');
    // Check previous button does exist.
    $assert_session->buttonExists('edit-wizard-prev');
    // Check next button is labeled 'Preview'.
    $assert_session->buttonExists('edit-preview-next');
    // Check submit button does exist.
    $assert_session->buttonExists('edit-submit');

    // Move to preview.
    $edit = ['comments' => 'This is working fine.'];
    $this->submitForm($edit, 'Preview');
    // Check progress bar is set to 'Preview'.
    $this->assertCurrentPage('Preview', WebformInterface::PAGE_PREVIEW);
    // Check progress pages.
    $assert_session->responseContains('4 of 5');
    // Check progress percentage.
    $assert_session->pageTextContains('(75%)');

    // Check preview values.
    $assert_session->responseContains('<label>First Name</label>');
    $assert_session->responseContains('Jane');
    $assert_session->responseContains('<label>Last Name</label>');
    $assert_session->responseContains('Smith');
    $assert_session->responseContains('<label>Sex</label>');
    $assert_session->responseContains('Female');
    $assert_session->responseContains('<label>Email</label>');
    $assert_session->responseContains('<a href="mailto:janesmith@example.com">janesmith@example.com</a>');
    $assert_session->responseContains('<label>Phone</label>');
    $assert_session->responseContains('<a href="tel:111-111-1111">111-111-1111</a>');
    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.2',
      currentCallable: fn() => $assert_session->responseContains('<div class="webform-element webform-element-type-textarea js-form-item form-item form-type-item js-form-type-item form-item-comments js-form-item-comments form-no-label" id="test_form_wizard_advanced--comments">'),
      deprecatedCallable: fn() => $assert_session->responseContains('<div class="webform-element webform-element-type-textarea js-form-item form-item js-form-type-item form-item-comments js-form-item-comments form-no-label" id="test_form_wizard_advanced--comments">'),
    );
    $assert_session->responseContains('This is working fine.');

    // Submit the webform.
    $this->submitForm([], 'Submit');
    // Check progress bar is set to 'Complete'.
    $this->assertCurrentPage('Complete', WebformInterface::PAGE_CONFIRMATION);
    // Check progress pages.
    $assert_session->responseContains('5 of 5');
    // Check progress percentage.
    $assert_session->pageTextContains('(100%)');

    /* Custom wizard settings (using advanced wizard) */

    $this->drupalLogout();

    // Check global next and previous button labels.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_wizard_next_button_label', '{global wizard next}')
      ->set('settings.default_wizard_prev_button_label', '{global wizard previous}')
      ->save();
    $this->drupalGet('/webform/test_form_wizard_advanced');
    $this->submitForm([], '{global wizard next}');

    // Check progress bar.
    $assert_session->responseContains('class="webform-progress-bar"');
    // Check previous button.
    $assert_session->buttonExists('{global wizard previous}');
    // Check next button.
    $assert_session->buttonExists('{global wizard next}');

    // Add 'webform_actions' element.
    $webform->setElementProperties('actions', [
      '#type' => 'webform_actions',
      '#wizard_next__label' => '{webform wizard next}',
      '#wizard_prev__label' => '{webform wizard previous}',
      '#preview_next__label' => '{webform preview next}',
      '#preview_prev__label' => '{webform preview previous}',
    ]);
    $webform->save();

    // Check webform next and previous button labels.
    $this->drupalGet('/webform/test_form_wizard_advanced');
    $this->submitForm([], '{webform wizard next}');
    // Check previous button.
    $assert_session->buttonExists('{webform wizard previous}');
    // Check next button.
    $assert_session->buttonExists('{webform wizard next}');

    // Check custom next and previous button labels.
    $elements = Yaml::decode($webform->get('elements'));
    $elements['contact']['#next_button_label'] = '{elements wizard next}';
    $elements['contact']['#prev_button_label'] = '{elements wizard previous}';
    $webform->set('elements', Yaml::encode($elements));
    $webform->save();
    $this->drupalGet('/webform/test_form_wizard_advanced');
    $this->submitForm([], '{webform wizard next}');

    // Check previous button.
    $assert_session->buttonExists('{elements wizard previous}');
    // Check next button.
    $assert_session->buttonExists('{elements wizard next}');

    // Check webform next and previous button labels.
    $webform->setSettings([
      'wizard_progress_bar' => FALSE,
      'wizard_progress_pages' => TRUE,
      'wizard_progress_percentage' => TRUE,
    ] + $webform->getSettings());
    $webform->save();
    $this->drupalGet('/webform/test_form_wizard_advanced');

    // Check no progress bar.
    $assert_session->responseNotContains('class="webform-progress-bar"');
    // Check progress pages.
    $assert_session->responseContains('1 of 5');
    // Check progress percentage.
    $assert_session->pageTextContains('(0%)');

    // Check global complete labels.
    $webform->setSettings([
      'wizard_progress_bar' => TRUE,
    ] + $webform->getSettings());
    $webform->save();
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_wizard_confirmation_label', '{global complete}')
      ->save();
    $this->drupalGet('/webform/test_form_wizard_advanced');
    $assert_session->responseContains('{global complete}');

    // Check webform complete label.
    $webform->setSettings([
      'wizard_progress_bar' => TRUE,
      'wizard_confirmation_label' => '{webform complete}',
    ] + $webform->getSettings());
    $webform->save();
    $this->drupalGet('/webform/test_form_wizard_advanced');
    $assert_session->responseContains('{webform complete}');

    // Check webform exclude complete.
    $webform->setSettings([
      'wizard_confirmation' => FALSE,
    ] + $webform->getSettings());
    $webform->save();
    $this->drupalGet('/webform/test_form_wizard_advanced');

    // Check complete label.
    $assert_session->responseContains('class="webform-progress-bar"');
    // Check complete is missing from confirmation page.
    $assert_session->responseNotContains('{webform complete}');
    $this->drupalGet('/webform/test_form_wizard_advanced/confirmation');
    $assert_session->responseNotContains('class="webform-progress-bar"');
  }

  /**
   * {@inheritdoc}
   */
  protected function assertCurrentPage($title, $page): void {
    parent::assertCurrentPage($title, $page);
    if ($page !== WebformInterface::PAGE_CONFIRMATION) {
      $this->assertSession()->responseContains('<h3 class="webform-section-title">' . $title . '</h3>');
    }
  }

}
