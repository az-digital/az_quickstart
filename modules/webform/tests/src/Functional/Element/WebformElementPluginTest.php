<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for the webform element plugin.
 *
 * @group webform
 */
class WebformElementPluginTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_test_element'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_plugin'];

  /**
   * Tests webform element plugin.
   */
  public function testElementPlugin() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    /* ********************************************************************** */
    // Dependencies. @see hook_webform_element_info_alter()
    /* ********************************************************************** */

    // Check that managed_file and webform_term-select are not available when
    // dependent modules are not installed.
    $this->drupalGet('/admin/reports/webform-plugins/elements');
    $assert_session->responseNotContains('<td><div class="webform-form-filter-text-source">managed_file</div></td>');
    $assert_session->responseNotContains('<td><div class="webform-form-filter-text-source">webform_term_select</div></td>');

    // Install file and taxonomy module.
    \Drupal::service('module_installer')->install(['file', 'taxonomy']);

    // Check that managed_file and webform_term-select are available when
    // dependent modules are installed.
    $this->drupalGet('/admin/reports/webform-plugins/elements');
    $assert_session->responseContains('<td><div class="webform-form-filter-text-source">managed_file</div></td>');
    $assert_session->responseContains('<td><div class="webform-form-filter-text-source">webform_term_select</div></td>');

    /* ********************************************************************** */
    // Plugin hooks.
    /* ********************************************************************** */

    // Get the webform test element.
    $webform_plugin_test = Webform::load('test_element_plugin');

    // Check prepare and setDefaultValue().
    $this->drupalGet('/webform/test_element_plugin');
    $assert_session->responseContains('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:preCreate');
    $assert_session->responseContains('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:postCreate');
    $assert_session->responseContains('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:prepare');
    $assert_session->responseContains('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:setDefaultValue');

    // Check save.
    $sid = $this->postSubmission($webform_plugin_test);
    $webform_submission = WebformSubmission::load($sid);
    $assert_session->responseContains('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:preCreate');
    $assert_session->responseContains('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:prepare');
    $assert_session->responseContains('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:setDefaultValue');
    $assert_session->responseContains('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement::validate');
    $assert_session->responseContains('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:preSave');
    $assert_session->responseContains('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:postSave insert');
    $assert_session->responseContains('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:postLoad');

    // Check update.
    $this->drupalGet('/admin/structure/webform/manage/test_element_plugin/submission/' . $sid . '/edit');
    $this->submitForm([], 'Save');
    $assert_session->responseContains('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:postLoad');
    $assert_session->responseContains('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:prepare');
    $assert_session->responseContains('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:setDefaultValue');
    $assert_session->responseContains('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement::validate');
    $assert_session->responseContains('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:preSave');
    $assert_session->responseContains('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:postSave update');

    // Check HTML.
    $this->drupalGet('/admin/structure/webform/manage/test_element_plugin/submission/' . $sid);
    $assert_session->responseContains('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:postLoad');
    $assert_session->responseContains('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:formatHtml');
    $assert_session->responseContains('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:formatText');

    // Check plain text.
    $this->drupalGet('/admin/structure/webform/manage/test_element_plugin/submission/' . $sid . '/text');
    $assert_session->responseContains('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:postLoad');
    $assert_session->responseContains('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:formatText');

    // Check delete.
    $this->drupalGet('/admin/structure/webform/manage/test_element_plugin/submission/' . $sid . '/delete');
    $this->submitForm([], 'Delete');
    $assert_session->responseContains('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:preDelete');
    $assert_session->responseContains('Invoked: Drupal\webform_test_element\Plugin\WebformElement\WebformTestElement:postDelete');
    $assert_session->responseContains('<em class="placeholder">Test: Element: Test (plugin): Submission #' . $webform_submission->serial() . '</em> has been deleted.');
  }

}
