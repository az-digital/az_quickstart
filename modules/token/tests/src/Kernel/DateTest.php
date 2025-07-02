<?php

namespace Drupal\Tests\token\Kernel;

/**
 * Tests date tokens.
 *
 * @group token
 */
class DateTest extends TokenKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system', 'token_module_test']);
  }

  function testDateTokens() {
    $tokens = [
      'token_module_test' => '1984',
      'invalid_format' => NULL,
    ];

    $this->assertTokens('date', ['date' => 453859200], $tokens);
  }

}
