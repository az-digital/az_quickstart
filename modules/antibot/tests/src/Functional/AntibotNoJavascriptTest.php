<?php

namespace Drupal\Tests\antibot\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests Antibot when JavaScript is disabled.
 *
 * @group antibot
 */
class AntibotNoJavascriptTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['antibot'];

  /**
   * Default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests antibot when JavaScript is disabled.
   *
   * BrowserTestBase tests are, by design, non-JavaScript tests so we're having
   * the perspective of bot trying to post a form.
   */
  public function testNoJavaScript() {
    $this->drupalGet('/user/password');
    $this->submitForm([
      'name' => $this->randomMachineName(),
    ], 'Submit');

    // Check the we reached the antibot closed road when the form is posted by a
    // bot even having JavaScript capabilities..
    $this->assertSession()->addressEquals('/antibot');
    $this->assertSession()->pageTextContains('Submission failed');
    $this->assertSession()->pageTextContains('You have reached this page because you submitted a form that required JavaScript to be enabled on your browser. This protection is in place to attempt to prevent automated submissions made on forms. Please return to the page that you came from and enable JavaScript on your browser before attempting to submit the form again.');
  }

}
