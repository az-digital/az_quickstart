<?php

namespace Drupal\Tests\config_readonly\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Base Class for shared Read Only test functionality.
 *
 * @group ConfigReadOnly
 */
abstract class ReadOnlyConfigTestBase extends BrowserTestBase {

  /**
   * User account with administrative permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * Read-only message.
   *
   * @var string
   */
  protected $message = 'This form will not be saved because the configuration active store is read-only.';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Turn on read-only mode.
   */
  protected function turnOnReadOnlySetting() {
    $settings['settings']['config_readonly'] = (object) [
      'value' => TRUE,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
  }

  /**
   * Turn off read-only mode.
   */
  protected function turnOffReadOnlySetting() {
    $settings['settings']['config_readonly'] = (object) [
      'value' => FALSE,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
  }

}
