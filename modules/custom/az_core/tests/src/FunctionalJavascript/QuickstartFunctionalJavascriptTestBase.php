<?php

namespace Drupal\Tests\az_core\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\az_core\Traits\AllowDrupalLoginSetupTrait;

/**
 * Provides helper methods for Quickstart tests.
 */
abstract class QuickstartFunctionalJavascriptTestBase extends WebDriverTestBase {
  use AllowDrupalLoginSetupTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->enableDrupalLogin();
  }

}
