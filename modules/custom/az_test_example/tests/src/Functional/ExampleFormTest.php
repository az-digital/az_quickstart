<?php

namespace Drupal\Tests\az_test_example\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Simple test to check that an example configuration form works.
 *
 * @group az_test_example
 */
class ExampleFormTest extends BrowserTestBase {

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'az_quickstart';

  /**
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['az_test_example'];

  /**
   * A user with permission to administer site configuration and access admin pages.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser(['administer site configuration', 'access administration pages']);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests an example configuration form's ability to save its configuration.
   */
  public function testExampleConfigForm() {

    // Make sure we can reach our form.
    $this->drupalGet(Url::fromRoute('az_test_example.settings'));
    $this->assertSession()->statusCodeEquals(200);

    // Set up a test value to submit the form with.
    $test_value = 'V13872973987293472934723';
    $values = [
      'api_key' => $test_value,
    ];

    // Submit the form using the form id.
    $this->submitForm($values, t('Save configuration'), 'quickstart-test-example-form');

    // Retrieve the saved value from the configuration system.
    $saved_value = \Drupal::config('az_test_example.settings')->get('api_key');

    // The two values should be equal if the form operated correctly.
    $this->assertEqual($saved_value, $test_value);
  }

}
