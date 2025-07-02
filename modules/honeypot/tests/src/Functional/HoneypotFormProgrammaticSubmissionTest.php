<?php

declare(strict_types=1);

namespace Drupal\Tests\honeypot\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Tests\BrowserTestBase;

/**
 * Test programmatic submission of forms protected by Honeypot.
 *
 * @group honeypot
 */
class HoneypotFormProgrammaticSubmissionTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['honeypot', 'honeypot_test', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up required Honeypot configuration.
    $honeypot_config = \Drupal::configFactory()->getEditable('honeypot.settings');
    $honeypot_config->set('element_name', 'url');
    $honeypot_config->set('time_limit', 5);
    $honeypot_config->set('protect_all_forms', TRUE);
    $honeypot_config->set('log', FALSE);
    $honeypot_config->save();

    // cspell:ignore robo
    $this->drupalCreateUser([], 'robo-user');
  }

  /**
   * Trigger a programmatic form submission and verify the validation errors.
   */
  public function testProgrammaticFormSubmission(): void {
    $result = $this->drupalGet('/honeypot_test/submit_form');
    $form_errors = (array) Json::decode($result);
    $this->assertSession()->responseNotContains('There was a problem with your form submission. Please wait 6 seconds and try again.');
    $this->assertEmpty($form_errors, 'The were no validation errors when submitting the form.');
  }

}
