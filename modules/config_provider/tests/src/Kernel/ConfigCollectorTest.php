<?php

namespace Drupal\Tests\config_provider\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\config_provider\Plugin\ConfigCollector;

/**
 * Test description.
 *
 * @group config_provider
 */
class ConfigCollectorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config_provider',
    'config_provider_foo_provider_test',
    'config_provider_foo_consumer_test',
  ];

  /**
   * Test Configuration Provider collector collects Foo provider and config.
   */
  public function testFooProvider() {
    // Avoid ConfigCollector deprecation message, requiring the install profile.
    $this->setInstallProfile('testing');

    $collector = $this->container->get('config_provider.collector');
    $collector->addInstallableConfig();
    $storage = $this->container->get('config_provider.storage');

    $this->assertTrue($storage->exists('foo.whatever.settings'));
    $foo = $storage->read('foo.whatever.settings');
    $this->assertTrue($foo['foo']);
    $this->assertFalse($foo['bar']);
  }

  /**
   * Tests ConfigCollector constructor deprecation error.
   *
   * Verifies that instantiating a ConfigCollector object without the
   * extension_path_resolver argument triggers the expected deprecation error.
   *
   * @group legacy
   */
  public function testConstructorDeprecation() {
    $this->expectDeprecation('Calling ConfigCollector::__construct() without the $extension_path_resolver argument is deprecated in config_provider:3.0.0-alpha2 and it will be required in config_provider:3.1.0. See https://www.drupal.org/project/config_provider/issues/3511302');
    $collector = new ConfigCollector(
      $this->container->get('config.factory'),
      $this->container->get('config.storage'),
      $this->container->get('config.manager'),
      $this->container->get('config_provider.storage'),
      $this->container->get('plugin.manager.config_provider.processor'),
      $this->container->getParameter('install_profile'),
    );
    $this->assertIsObject($collector);
  }

}
