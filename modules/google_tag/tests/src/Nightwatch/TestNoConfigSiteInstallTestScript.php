<?php

declare(strict_types=1);

use Drupal\TestSite\TestSetupInterface;

/**
 * Setup for nightwatch tests.
 */
final class TestNoConfigSiteInstallTestScript implements TestSetupInterface {

  /**
   * {@inheritDoc}
   */
  public function setup() {
    // @phpstan-ignore-next-line
    \Drupal::service('module_installer')->install([
      'test_page_test',
      'token',
      'google_tag',
      'google_tag_test',
    ]);
  }

}
