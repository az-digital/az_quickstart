<?php

namespace Drupal\Tests\token\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Helper test class with some added functions for testing.
 */
abstract class TokenTestBase extends BrowserTestBase {

  use TokenTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['path', 'token', 'token_module_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
