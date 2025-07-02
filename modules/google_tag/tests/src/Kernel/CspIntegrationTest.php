<?php

declare(strict_types=1);

namespace Drupal\Tests\google_tag\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Csp integration event test.
 *
 * @group google_tag
 * @requires module csp
 */
final class CspIntegrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['google_tag'];

  /**
   * Tests container definition without csp module.
   */
  public function testWithoutCsp(): void {
    self::assertFalse(
      $this->container->has('google_tag.csp_subscriber')
    );
  }

  /**
   * Tests container definition with csp module.
   */
  public function testWithCsp(): void {
    $this->enableModules(['csp']);
    self::assertTrue(
      $this->container->has('google_tag.csp_subscriber')
    );
  }

}
