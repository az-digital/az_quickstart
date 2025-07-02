<?php

namespace Drupal\Tests\workbench_access\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests plugin discovery respects derivative discovery.
 *
 * @group workbench_access
 */
class WorkbenchAccessDerivativeDiscoveryTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'workbench_access',
    'workbench_access_test',
    'entity_test',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_test');
  }

  /**
   * Tests that derivative plugins work.
   */
  public function testDerivativeDiscovery() {
    $manager = $this->container->get('plugin.manager.workbench_access.scheme');
    foreach ($manager->getDefinitions() as $id => $name) {
      $this->assertNotNull($manager->createInstance($id));
    }
  }

}
