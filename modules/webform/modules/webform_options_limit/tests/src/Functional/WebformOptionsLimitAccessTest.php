<?php

namespace Drupal\Tests\webform_options_limit\Functional;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Webform options limit test.
 *
 * @group webform_options_limit
 */
class WebformOptionsLimitAccessTest extends WebformBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'webform',
    'webform_options_limit',
    'webform_options_limit_test',
  ];

  /**
   * Test options limit access.
   */
  public function testAccess() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_handler_options_limit');

    $this->postSubmission($webform);
    $this->postSubmission($webform);
    $this->postSubmission($webform);

    // Check that no one can access the options summary page.
    $this->drupalGet('/admin/structure/webform/manage/test_handler_options_limit/results/options-limit');
    $assert_session->statusCodeEquals(403);

    // Check that user with 'view any webform submission' permission can access
    // the options summary page.
    $this->drupalLogin($this->createUser(['view any webform submission']));
    $this->drupalGet('/admin/structure/webform/manage/test_handler_options_limit/results/options-limit');
    $assert_session->statusCodeEquals(200);

    // Check that options summary page is only available to webforms with
    // options limit handler.
    $this->drupalGet('/admin/structure/webform/manage/contact/results/options-limit');
    $assert_session->statusCodeEquals(403);
  }

}
