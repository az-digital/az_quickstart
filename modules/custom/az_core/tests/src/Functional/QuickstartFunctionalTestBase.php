<?php

namespace Drupal\Tests\az_core\Functional;

use Drupal\Tests\az_core\Traits\AllowDrupalLoginSetupTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Provides helper methods for Quickstart tests.
 */
abstract class QuickstartFunctionalTestBase extends BrowserTestBase {
  use AllowDrupalLoginSetupTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->enableDrupalLogin();
  }

}
