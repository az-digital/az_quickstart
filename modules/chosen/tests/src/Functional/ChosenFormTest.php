<?php

namespace Drupal\Tests\chosen\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Chosen form API test.
 *
 * @group chosen
 */
class ChosenFormTest extends BrowserTestBase {
  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['chosen', 'chosen_test'];

  /**
   * Test the form page.
   */
  public function testFormPage() {
    $this->drupalGet('chosen-test');
    $this->assertSession()->pageTextContains('Select');
    $this->assertSession()->elementExists('css', 'select#edit-select.chosen-enable');
  }

}
