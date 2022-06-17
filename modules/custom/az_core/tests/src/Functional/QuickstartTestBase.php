<?php

namespace Drupal\Tests\az_core\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Provides helper methods for Quickstart tests.
 */
abstract class QuickstartTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Turn off az_cas disable_login_form setting.
    $config = $this
      ->config('az_cas.settings');
    $config
      ->set('disable_login_form', FALSE)
      ->save();

    // The menu router info needs to be rebuilt after updating this setting so
    // the routeSubscriber runs again.
    $this->container->get('router.builder')->rebuild();
  }

}
