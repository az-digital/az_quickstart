<?php

namespace Drupal\Tests\webform\Functional\Form;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform form validation.
 *
 * @group webform
 */
class WebformFormValidateTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_test_validate'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_validate'];

  /**
   * Tests form (custom) validation.
   */
  public function testValidate() {
    $assert_session = $this->assertSession();

    /* Test form#validate webform handling */
    $webform_validate = Webform::load('test_form_validate');
    $this->postSubmission($webform_validate, []);
    $assert_session->responseContains('Custom element is required.');

    $this->postSubmission($webform_validate, ['custom' => 'value']);
    $assert_session->responseNotContains('Custom element is required.');
  }

}
