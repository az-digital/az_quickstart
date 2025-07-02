<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform actions element.
 *
 * @group webform
 */
class WebformElementActionsTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_actions', 'test_element_actions_buttons'];

  /**
   * Tests actions element.
   */
  public function testActions() {
    global $base_path;

    $assert_session = $this->assertSession();

    $webform = Webform::load('test_element_actions');

    /* ********************************************************************** */
    /* Test webform actions */
    /* ********************************************************************** */

    // Get form.
    $this->drupalGet('/webform/test_element_actions');

    // Check custom actions.
    $assert_session->responseContains('<div style="border: 2px solid red; padding: 10px" data-drupal-selector="edit-actions-custom" class="form-actions webform-actions js-form-wrapper form-wrapper" id="edit-actions-custom">');
    $assert_session->responseContains('<input formnovalidate="formnovalidate" class="webform-button--draft custom-draft button js-form-submit form-submit" style="font-weight: bold" data-custom-draft data-drupal-selector="edit-actions-custom-draft" type="submit" id="edit-actions-custom-draft" name="op" value="{Custom draft}" />');
    $assert_session->responseContains('<input class="webform-button--next custom-wizard-next button js-form-submit form-submit" style="font-weight: bold" data-custom-wizard-next data-drupal-selector="edit-actions-custom-wizard-next" type="submit" id="edit-actions-custom-wizard-next" name="op" value="{Custom wizard next}" />');
    $assert_session->responseContains('<input formnovalidate="formnovalidate" class="webform-button--reset custom-reset button js-form-submit form-submit" style="font-weight: bold" data-custom-reset data-drupal-selector="edit-actions-custom-reset" type="submit" id="edit-actions-custom-reset" name="op" value="{Custom reset}" />');

    // Check wizard next.
    $this->assertCssSelect('[id="edit-actions-wizard-next-wizard-next"]');
    $this->assertNoCssSelect('[id="edit-actions-wizard-prev-wizard-prev"]');

    // Move to next page.
    $this->submitForm([], 'Next >');

    // Check no wizard next.
    $this->assertNoCssSelect('[id="edit-actions-wizard-next-wizard-next"]');
    $this->assertCssSelect('[id="edit-actions-wizard-prev-wizard-prev"]');

    // Move to preview.
    $this->submitForm([], 'Preview');

    // Check submit button.
    $this->assertCssSelect('[id="edit-actions-submit-submit"]');

    // Check reset button.
    $this->assertCssSelect('[id="edit-actions-reset-reset"]');

    // Submit form.
    $this->submitForm([], 'Submit');
    $sid = $this->getLastSubmissionId($webform);

    // Check no actions.
    $this->assertNoCssSelect('.form-actions');

    // Login as admin.
    $this->drupalLogin($this->rootUser);

    // Check custom update action.
    $this->drupalGet("/admin/structure/webform/manage/test_element_actions/submission/$sid/edit");
    $this->submitForm([], 'Next >');
    $assert_session->responseContains('<input class="webform-button--submit custom-update button button--primary js-form-submit form-submit" style="font-weight: bold" data-custom-update data-drupal-selector="edit-actions-custom-submit" type="submit" id="edit-actions-custom-submit" name="op" value="{Custom update}" />');

    // Check custom delete action.
    $this->drupalGet('/webform/test_element_actions');
    $this->submitForm([], 'Save Draft');
    $sid = $this->getLastSubmissionId($webform);
    // @todo Remove once Drupal 10.1.x is only supported.
    if (floatval(\Drupal::VERSION) >= 10.1) {
      $assert_session->responseContains('<a href="' . $base_path . 'admin/structure/webform/manage/test_element_actions/submission/' . $sid . '/delete?destination=' . $base_path . 'webform/test_element_actions" class="button button--danger use-ajax custom-delete" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:880}" style="font-weight: bold" data-custom-delete data-drupal-selector="edit-actions-custom-delete" id="edit-actions-custom-delete" hreflang="en">{Custom delete}</a>');
    }
    else {
      $assert_session->responseContains('<a href="' . $base_path . 'admin/structure/webform/manage/test_element_actions/submission/' . $sid . '/delete?destination=' . $base_path . 'webform/test_element_actions" class="button button--danger custom-delete" style="font-weight: bold" data-custom-delete data-drupal-selector="edit-actions-custom-delete" id="edit-actions-custom-delete" hreflang="en">{Custom delete}</a>');
    }
    $this->assertCssSelect('[id="edit-actions-delete"]');

    /* ********************************************************************** */
    /* Test actions buttons */
    /* ********************************************************************** */

    $webform = Webform::load('test_element_actions_buttons');

    $this->drupalLogin($this->rootUser);

    $this->drupalGet('/webform/test_element_actions_buttons');

    // Check draft button.
    $assert_session->responseContains('<input formnovalidate="formnovalidate" class="webform-button--draft draft_button_attributes button js-form-submit form-submit" style="color: blue" data-drupal-selector="edit-actions-draft" type="submit" id="edit-actions-draft" name="op" value="Save Draft" />');
    // Check next button.
    $assert_session->responseContains('<input class="webform-button--next wizard_next_button_attributes button js-form-submit form-submit" style="color: yellow" data-drupal-selector="edit-actions-wizard-next" type="submit" id="edit-actions-wizard-next" name="op" value="Next &gt;" />');

    $this->drupalGet('/webform/test_element_actions_buttons');
    $this->submitForm([], 'Next >');

    // Check previous button.
    $assert_session->responseContains('<input formnovalidate="formnovalidate" class="webform-button--previous wizard_prev_button_attributes button js-form-submit form-submit" style="color: yellow" data-drupal-selector="edit-actions-wizard-prev" type="submit" id="edit-actions-wizard-prev" name="op" value="&lt; Previous" />');
    // Check preview button.
    $assert_session->responseContains('<input class="webform-button--preview preview_next_button_attributes button js-form-submit form-submit" style="color: orange" data-drupal-selector="edit-actions-preview-next" type="submit" id="edit-actions-preview-next" name="op" value="Preview" />');

    $this->submitForm([], 'Preview');

    // Check previous button.
    $assert_session->responseContains('<input formnovalidate="formnovalidate" class="webform-button--previous preview_prev_button_attributes button js-form-submit form-submit" style="color: orange" data-drupal-selector="edit-actions-preview-prev" type="submit" id="edit-actions-preview-prev" name="op" value="&lt; Previous" />');
    // Check submit button.
    $assert_session->responseContains('<input class="webform-button--submit form_submit_attributes button button--primary js-form-submit form-submit" style="color: green" data-drupal-selector="edit-actions-submit" type="submit" id="edit-actions-submit" name="op" value="Submit" />');

    $this->submitForm([], 'Submit');
    $sid = $this->getLastSubmissionId($webform);

    // Check update button.
    $this->drupalGet("/admin/structure/webform/manage/test_element_actions_buttons/submission/$sid/edit");
    $this->submitForm([], 'Next >');
    $assert_session->responseContains('<input class="webform-button--submit form_update_attributes button button--primary js-form-submit form-submit" style="color: purple" data-drupal-selector="edit-actions-submit" type="submit" id="edit-actions-submit" name="op" value="Save" />');
  }

}
