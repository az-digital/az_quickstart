<?php

namespace Drupal\Tests\workbench_access\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Defines a class for testing circular dependencies.
 *
 * @group workbench_access
 */
class CircularDependencyTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_moderation',
    'workflows',
    'workbench_access',
    'workbench_access_circular_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['workbench_access']);
  }

  /**
   * Tests circular dependencies.
   */
  public function testCircularDependencies() {
    $this->assertTrue(TRUE);
  }

}
