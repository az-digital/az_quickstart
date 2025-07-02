<?php

namespace Drupal\Tests\webform\Functional\Handler;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform handler plugin conditions.
 *
 * @group webform
 */
class WebformHandlerConditionsTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_test_handler'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_handler_conditions'];

  /**
   * Tests webform handler plugin conditions.
   */
  public function testConditions() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_handler_conditions');

    $this->drupalGet('/webform/test_handler_conditions');

    // Check no triggers.
    $assert_session->responseContains('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $assert_session->responseContains('Invoked test_b: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $assert_session->responseContains('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');
    $assert_session->responseContains('Invoked test_b: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');

    // Check post submission no trigger.
    $this->postSubmission($webform);
    $assert_session->responseContains('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $assert_session->responseContains('Invoked test_b: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $assert_session->responseContains('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');
    $assert_session->responseContains('Invoked test_b: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');

    // Trigger only A handler.
    $this->postSubmission($webform, ['trigger_a' => TRUE]);

    // Check non submission hooks are executed.
    $assert_session->responseContains('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $assert_session->responseContains('Invoked test_b: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $assert_session->responseContains('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');
    $assert_session->responseContains('Invoked test_b: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');

    // Check trigger A submission hooks are executed.
    $assert_session->responseContains('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:validateForm');
    $assert_session->responseContains('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:submitForm');
    $assert_session->responseContains('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preSave');
    $assert_session->responseContains('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postSave insert');
    $assert_session->responseContains('Test A');
    $assert_session->responseContains('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:confirmForm');

    // Trigger only B handler.
    $this->postSubmission($webform, ['trigger_b' => TRUE]);

    // Check non submission hooks are executed.
    $assert_session->responseContains('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $assert_session->responseContains('Invoked test_b: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $assert_session->responseContains('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');
    $assert_session->responseContains('Invoked test_b: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');

    // Check trigger A submission hooks are no executed.
    $assert_session->responseNotContains('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:validateForm');
    $assert_session->responseNotContains('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:submitForm');
    $assert_session->responseNotContains('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preSave');
    $assert_session->responseNotContains('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postSave insert');
    $assert_session->responseNotContains('Test A');
    $assert_session->responseNotContains('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:confirmForm');

    // Check trigger B submission hooks are executed.
    $assert_session->responseContains('Invoked test_b: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:validateForm');
    $assert_session->responseContains('Invoked test_b: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:submitForm');
    $assert_session->responseContains('Invoked test_b: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preSave');
    $assert_session->responseContains('Invoked test_b: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postSave insert');
    $assert_session->responseContains('Test B');
    $assert_session->responseContains('Invoked test_b: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:confirmForm');
  }

}
