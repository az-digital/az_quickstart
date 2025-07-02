<?php

declare(strict_types=1);

namespace Drupal\Tests\config_ignore_readonly\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test description.
 *
 * @group config_ignore_readonly
 */
class ConfigIgnoreReadonlyTest extends BrowserTestBase {

  /**
   * User account with administrative permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;


  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config',
    'config_ignore_readonly',
    'config_ignore',
    'config_readonly',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // We need an admin user.
    $this->adminUser = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($this->adminUser);

    // Set up test config.
    $config = $this->config('config_ignore.settings');
    $config->set('ignored_config_entities', [
      'automated_cron.settings',
      'system.cron',
    ]);
    $config->save();
  }

  /**
   * Test callback.
   */
  public function testWhitelistHook(): void {
    $message = 'This form will not be saved because the configuration active store is read-only';
    $this->turnOnReadOnlySetting();
    $assert_session = $this->assertSession();
    $this->drupalGet('admin/config/system/site-information');
    $assert_session->pageTextContainsOnce($message);
    $this->drupalGet('admin/config/system/cron');
    // Warning not shown on cron config page.
    $assert_session->pageTextNotContains($message);
  }

  /**
   * Turn on read-only mode.
   *
   * @see \Drupal\Tests\config_readonly\Functional\ReadOnlyConfigTest::turnOnReadOnlySetting
   */
  protected function turnOnReadOnlySetting(): void {
    $settings['settings']['config_readonly'] = (object) [
      'value' => TRUE,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
  }

}
