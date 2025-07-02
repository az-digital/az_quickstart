<?php

declare(strict_types=1);

namespace Drupal\Tests\honeypot\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests legacy honeypot functionality.
 *
 * @group honeypot
 * @group legacy
 */
class HoneypotLegacyTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['honeypot', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('honeypot', ['honeypot_user']);
    $this->installConfig(['honeypot']);
  }

  /**
   * Tests the deprecation message for honeypot_get_protected_forms().
   */
  public function testGetProtectedForms(): void {
    $this->expectDeprecation("honeypot_get_protected_forms() is deprecated in honeypot:2.1.0 and is removed from honeypot:3.0.0. Use the 'honeypot' service instead. For example, \Drupal::service('honeypot')->getProtectedForms(). See https://www.drupal.org/node/2949447");
    $this->assertIsArray(honeypot_get_protected_forms());
  }

  /**
   * Tests the deprecation message for honeypot_add_form_protection().
   */
  public function testAddFormProtection(): void {
    $this->expectDeprecation("honeypot_add_form_protection() is deprecated in honeypot:2.1.0 and is removed from honeypot:3.0.0. Use the 'honeypot' service instead. For example, \Drupal::service('honeypot')->addFormProtection(\$form, \$form_state, \$options). See https://www.drupal.org/node/2949447");
    $form = [];
    $form_state = new FormState();
    honeypot_add_form_protection($form, $form_state, ['honeypot']);
  }

  /**
   * Tests the deprecation message for honeypot_get_time_limit().
   */
  public function testGetTimeLimit(): void {
    $this->expectDeprecation("honeypot_get_time_limit() is deprecated in honeypot:2.1.0 and is removed from honeypot:3.0.0. Use the 'honeypot' service instead. For example, \Drupal::service('honeypot')->getTimeLimit(\$form_values). See https://www.drupal.org/node/2949447");
    $this->assertIsInt(honeypot_get_time_limit());
  }

  /**
   * Tests the deprecation message for honeypot_log_failure().
   */
  public function testLogFailure(): void {
    $this->expectDeprecation("honeypot_log_failure() is deprecated in honeypot:2.1.0 and is removed from honeypot:3.0.0. Use the 'honeypot' service instead. For example, \Drupal::service('honeypot')->logFailure(\$form_id, \$type). See https://www.drupal.org/node/2949447");
    honeypot_log_failure('user_login_form', 'honeypot');
  }

}
