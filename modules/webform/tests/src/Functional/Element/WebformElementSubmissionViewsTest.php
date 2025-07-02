<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for webform submission views element.
 *
 * @group webform
 */
class WebformElementSubmissionViewsTest extends WebformElementBrowserTestBase {

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
  protected static $testWebforms = ['test_element_submission_views'];

  /**
   * Test webform submission views element.
   */
  public function testSubmissionViews() {
    $assert_session = $this->assertSession();

    // Check global and webform rendering.
    $this->drupalGet('/webform/test_element_submission_views');
    $assert_session->responseContains('<th class="webform_submission_views_global-table--name_title_view webform-multiple-table--name_title_view">');
    $assert_session->responseContains('<th class="webform_submission_views_global-table--global_routes webform-multiple-table--global_routes">');
    $assert_session->responseContains('<th class="webform_submission_views_global-table--webform_routes webform-multiple-table--webform_routes">');
    $assert_session->responseContains('<th class="webform_submission_views_global-table--node_routes webform-multiple-table--node_routes">');
    $assert_session->responseContains('<th class="webform_submission_views-table--name_title_view webform-multiple-table--name_title_view">');
    $assert_session->responseNotContains('<th class="webform_submission_views-table--global_routes webform-multiple-table--global_routes">');
    $assert_session->responseContains('<th class="webform_submission_views-table--webform_routes webform-multiple-table--webform_routes">');
    $assert_session->responseContains('<th class="webform_submission_views-table--node_routes webform-multiple-table--node_routes">');

    // Check name validation.
    $this->drupalGet('/webform/test_element_submission_views');
    $edit = ['webform_submission_views_global[items][0][name]' => ''];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('Name is required');

    // Check view validation.
    $this->drupalGet('/webform/test_element_submission_views');
    $edit = ['webform_submission_views_global[items][0][view]' => ''];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('View name/display id is required.');

    // Check title validation.
    $this->drupalGet('/webform/test_element_submission_views');
    $edit = ['webform_submission_views_global[items][0][title]' => ''];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('Title is required.');

    // Check processing.
    $this->drupalGet('/webform/test_element_submission_views');
    $this->submitForm([], 'Submit');
    $assert_session->responseContains("webform_submission_views_global:
  admin:
    view: 'webform_submissions:embed_administer'
    title: Admin
    global_routes:
      - entity.webform_submission.collection
    webform_routes:
      - entity.webform.results_submissions
    node_routes:
      - entity.node.webform.results_submissions
webform_submission_views:
  admin:
    view: 'webform_submissions:embed_administer'
    title: Admin
    webform_routes:
      - entity.webform.results_submissions
    node_routes:
      - entity.node.webform.results_submissions");

    // Check processing empty record.
    $this->drupalGet('/webform/test_element_submission_views');
    $edit = [
      'webform_submission_views_global[items][0][name]' => '',
      'webform_submission_views_global[items][0][view]' => '',
      'webform_submission_views_global[items][0][title]' => '',
      'webform_submission_views_global[items][0][global_routes][entity.webform_submission.collection]' => FALSE,
      'webform_submission_views_global[items][0][webform_routes][entity.webform.results_submissions]' => FALSE,
      'webform_submission_views_global[items][0][node_routes][entity.node.webform.results_submissions]' => FALSE,
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseNotContains('Name is required');
    $assert_session->responseNotContains('View name/display id is required.');
    $assert_session->responseNotContains('Title is required.');
    $assert_session->responseContains("webform_submission_views_global: {  }
webform_submission_views:
  admin:
    view: 'webform_submissions:embed_administer'
    title: Admin
    webform_routes:
      - entity.webform.results_submissions
    node_routes:
      - entity.node.webform.results_submissions");

    // Uninstall the webform node module.
    $this->container->get('module_installer')->uninstall(['webform_node']);

    // Check global and webform rendering without node settings.
    $this->drupalGet('/webform/test_element_submission_views');
    $assert_session->responseNotContains('<th class="webform_submission_views_global-table--node_routes webform-multiple-table--node_routes">');
    $assert_session->responseNotContains('<th class="webform_submission_views-table--node_routes webform-multiple-table--node_routes">');

    // Check processing removes node settings.
    $this->drupalGet('/webform/test_element_submission_views');
    $this->submitForm([], 'Submit');
    $assert_session->responseContains("webform_submission_views_global:
  admin:
    view: 'webform_submissions:embed_administer'
    title: Admin
    global_routes:
      - entity.webform_submission.collection
    webform_routes:
      - entity.webform.results_submissions
webform_submission_views:
  admin:
    view: 'webform_submissions:embed_administer'
    title: Admin
    webform_routes:
      - entity.webform.results_submissions");

    // Uninstall the views module.
    $this->container->get('module_installer')->uninstall(['views']);

    // Check that element is completely hidden.
    $this->drupalGet('/webform/test_element_submission_views');
    $assert_session->responseNotContains('<th class="webform_submission_views_global-table--name_title_view webform-multiple-table--name_title_view">');
    $assert_session->responseNotContains('<th class="webform_submission_views-table--name_title_view webform-multiple-table--name_title_view">');

    // Check that value is preserved.
    $this->drupalGet('/webform/test_element_submission_views');
    $this->submitForm([], 'Submit');
    $assert_session->responseContains("webform_submission_views_global: {  }
webform_submission_views: {  }");
  }

}
