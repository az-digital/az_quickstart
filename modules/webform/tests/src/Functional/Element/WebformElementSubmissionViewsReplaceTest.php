<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for webform submission views replace element.
 *
 * @group webform
 */
class WebformElementSubmissionViewsReplaceTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['views', 'node', 'webform', 'webform_node'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_submission_views_r'];

  /**
   * Test webform submission views replace element.
   */
  public function testSubmissionViewsReplace() {
    $assert_session = $this->assertSession();

    // Check rendering.
    $this->drupalGet('/webform/test_element_submission_views_r');
    $assert_session->responseContains('<fieldset data-drupal-selector="edit-webform-submission-views-replace-global-global-routes" id="edit-webform-submission-views-replace-global-global-routes--wrapper" class="fieldgroup form-composite js-form-item form-item js-form-wrapper form-wrapper">');
    $assert_session->responseContains('<fieldset data-drupal-selector="edit-webform-submission-views-replace-global-webform-routes" id="edit-webform-submission-views-replace-global-webform-routes--wrapper" class="fieldgroup form-composite js-form-item form-item js-form-wrapper form-wrapper">');
    $assert_session->responseContains('<fieldset data-drupal-selector="edit-webform-submission-views-replace-global-node-routes" id="edit-webform-submission-views-replace-global-node-routes--wrapper" class="fieldgroup form-composite js-form-item form-item js-form-wrapper form-wrapper">');

    // Check that the webform replace element is hidden.
    $assert_session->responseNotContains('<fieldset data-drupal-selector="edit-webform-submission-views-replace-webform-routes" id="edit-webform-submission-views-replace-webform-routes--wrapper" class="fieldgroup form-composite js-form-item form-item js-form-wrapper form-wrapper">');
    $assert_session->responseNotContains('<fieldset data-drupal-selector="edit-webform-submission-views-replace-node-routes" id="edit-webform-submission-views-replace-node-routes--wrapper" class="fieldgroup form-composite js-form-item form-item js-form-wrapper form-wrapper">');

    // Check processing clears hidden webform_submission_views_replace.
    $this->drupalGet('/webform/test_element_submission_views_r');
    $this->submitForm([], 'Submit');
    $assert_session->responseContains("webform_submission_views_replace_global:
  global_routes:
    - entity.webform_submission.collection
  webform_routes:
    - entity.webform.results_submissions
  node_routes:
    - entity.node.webform.results_submissions
webform_submission_views_replace: {  }");

    // Clear default_submission_views_replace.
    \Drupal::configFactory()
      ->getEditable('webform.settings')
      ->set('settings.default_submission_views_replace', [
        'global_routes' => [],
        'webform_routes' => [],
        'node_routes' => [],
      ])
      ->save();

    // Check that the webform replace element is visible.
    $this->drupalGet('/webform/test_element_submission_views_r');
    $assert_session->responseContains('<fieldset data-drupal-selector="edit-webform-submission-views-replace-webform-routes" id="edit-webform-submission-views-replace-webform-routes--wrapper" class="fieldgroup form-composite js-form-item form-item js-form-wrapper form-wrapper">');
    $assert_session->responseContains('<fieldset data-drupal-selector="edit-webform-submission-views-replace-node-routes" id="edit-webform-submission-views-replace-node-routes--wrapper" class="fieldgroup form-composite js-form-item form-item js-form-wrapper form-wrapper">');

    // Check processing with webform replace element is visible.
    $this->drupalGet('/webform/test_element_submission_views_r');
    $this->submitForm([], 'Submit');
    $assert_session->responseContains("webform_submission_views_replace_global:
  global_routes:
    - entity.webform_submission.collection
  webform_routes:
    - entity.webform.results_submissions
  node_routes:
    - entity.node.webform.results_submissions
webform_submission_views_replace:
  webform_routes:
    - entity.webform.results_submissions
  node_routes:
    - entity.node.webform.results_submissions");
  }

}
