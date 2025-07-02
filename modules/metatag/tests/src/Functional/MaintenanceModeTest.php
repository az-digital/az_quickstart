<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\Tests\BrowserTestBase;

/**
 * Verify handling of maintenance mode pages.
 *
 * @group metatag
 */
class MaintenanceModeTest extends BrowserTestBase {

  // Contains helper methods.
  use MetatagHelperTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // Modules for core functionality.
    'node',
    'field',
    'field_ui',
    'user',

    // Contrib dependencies.
    'token',

    // This module.
    'metatag',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Put the site into maintenance mode, see what the meta tags are.
   */
  public function testUser1() {
    // Load the user 1 profile page.
    $this->drupalGet('/user/1');
    $session = $this->assertSession();
    // Confirm the page title is correct.
    $session->responseContains('<title>Access denied | ');
    $session->responseNotContains('<title>admin | ');
    $session->responseNotContains('<title>Site under maintenance | ');

    // Put the site into maintenance mode.
    \Drupal::state()->set('system.maintenance_mode', TRUE);
    Cache::invalidateTags(['rendered']);

    // Load the user 1 profile page again.
    $this->drupalGet('/user/1');
    // Confirm the page title has changed.
    $session->responseNotContains('<title>Access denied | ');
    $session->responseNotContains('<title>admin | ');
    $session->responseContains('<title>Site under maintenance | ');
  }

}
