<?php

namespace Drupal\Tests\config_inspector\Kernel;

use Drupal\config_inspector\ConfigInspectorManager;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests configuration inspector manager.
 *
 * @group config_inspector
 */
class ConfigInspectorManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'config_inspector'];

  /**
   * Tests retrieving definitions list.
   */
  public function testGetDefinitionsMethod() {
    $manager = \Drupal::service('config_inspector.manager');
    $this->assertInstanceOf(ConfigInspectorManager::class, $manager);
    $this->assertArrayHasKey('label', $manager->getDefinition('user.settings'));
  }

}
