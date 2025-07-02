<?php

namespace Drupal\Tests\token\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\token\Functional\TokenTestTrait;

/**
 * Helper test class with some added functions for testing.
 */
abstract class TokenKernelTestBase extends KernelTestBase {

  use TokenTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'path',
    'token',
    'token_module_test',
    'system',
    'user',
    'path_alias',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('path_alias');
    \Drupal::service('router.builder')->rebuild();
    $this->installConfig(['system']);
  }

}
