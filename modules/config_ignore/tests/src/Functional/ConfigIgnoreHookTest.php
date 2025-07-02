<?php

namespace Drupal\Tests\config_ignore\Functional;

/**
 * Test hook implementation of another module.
 *
 * @group config_ignore
 */
class ConfigIgnoreHookTest extends ConfigIgnoreBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config',
    'config_ignore_hook_test',
  ];

  /**
   * Test hook implementation of another module.
   */
  public function testSettingsAlterHook() {

    $this->config('system.site')->set('name', 'Test import')->save();

    $this->doExport();

    $this->config('system.site')->set('name', 'Changed title')->save();

    $this->doImport();

    // Test if the `config_ignore_hook_test` module got to ignore the site name
    // config.
    $this->assertEquals('Changed title', $this->config('system.site')->get('name'));

  }

  /**
   * Test alter hook values are cached unless invalidated.
   *
   * Its important import/export is called directly, if called by a browser then
   * static cache will not be present.
   *
   * @covers \Drupal\config_ignore\EventSubscriber\ConfigIgnoreEventSubscriber::getRules
   * @covers \Drupal\config_ignore\EventSubscriber\ConfigIgnoreEventSubscriber::invalidateTags
   */
  public function testAlterHookStaticCache() {
    // Each of these are cloned since normally result of transformStorage
    // statically cached by \Drupal\Core\Config\ManagedStorage::$manager.
    $sourceOriginal = $this->container->get('config.storage.export');
    $destinationOriginal = $this->container->get('config.storage.sync');

    // Never called.
    $this->assertNull($this->getAlterCallCount());

    // Initial call caches.
    $this->copyConfig(clone $sourceOriginal, clone $destinationOriginal);
    $this->assertEquals(1, $this->getAlterCallCount());

    // Subsequent calls read from cache.
    $this->copyConfig(clone $sourceOriginal, clone $destinationOriginal);
    $this->assertEquals(1, $this->getAlterCallCount());

    // Writing a new value to our config triggers cache tag invalidation.
    // This value itself is inconsequential.
    $this->config('config_ignore.settings')->set('ignored_config_entities', ['foo.bar'])->save();

    $this->copyConfig(clone $sourceOriginal, clone $destinationOriginal);
    $this->assertEquals(2, $this->getAlterCallCount());
  }

  /**
   * Get call count of alter hook.
   *
   * @return int|null
   *   Call count, or NULL if not called.
   */
  protected function getAlterCallCount() {
    return \Drupal::state()->get('hook_config_ignore_settings_alter__call_count');
  }

}
