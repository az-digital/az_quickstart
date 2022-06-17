<?php

namespace Drupal\Tests\az_core\Traits;

/**
 * Turns off az_cas disable_login_form setting.
 */
trait AllowDrupalLoginSetupTrait {

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
