<?php

namespace Drupal\Tests\migmag\Kernel;

use Drupal\Core\Cache\MemoryCounterBackend;
use Drupal\Core\Database\Database;
use Drupal\Tests\migmag\Traits\MigMagMigrationTestDatabaseTrait;
use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;
use Drupal\migrate\Plugin\MigrateDestinationInterface;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Row;

/**
 * Base class for testing migrate source plugins with native databases.
 *
 * Most of the methods are copied from MigrateTestBase and are slightly
 * modified.
 *
 * @see \Drupal\Tests\migrate\Kernel\MigrateTestBase
 */
abstract class MigMagNativeMigrateSqlTestBase extends MigrateSqlSourceTestBase {

  use MigMagMigrationTestDatabaseTrait;

  /**
   * The source database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $sourceDatabase;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $destination_plugin = $this->prophesize(MigrateDestinationInterface::class);
    $destination_plugin->getPluginId()->willReturn($this->randomMachineName(16));
    $this->migration->getDestinationPlugin()->willReturn(
      $destination_plugin->reveal()
    );

    $this->createSourceMigrationConnection();
    $this->sourceDatabase = Database::getConnection('default', 'migrate');
  }

  /**
   * {@inheritdoc}
   *
   * @dataProvider providerSource
   */
  public function testSource(array $source_data, array $expected_data, $expected_count = NULL, array $configuration = [], $high_water = NULL, $expected_cache_key = NULL): void {
    $this->importSourceDatabase($source_data);
    $plugin = $this->getPlugin($configuration);
    $clone_plugin = clone $plugin;

    // All source plugins must define IDs.
    $this->assertNotEmpty($plugin->getIds());

    // If there is a high water mark, set it in the high water storage.
    if (isset($high_water)) {
      $this->container
        ->get('keyvalue')
        ->get('migrate:high_water')
        ->set($this->migration->reveal()->id(), $high_water);
    }

    // Tests the cacheability of the plugin.
    $cache = \Drupal::cache('migrate');
    assert($cache instanceof MemoryCounterBackend);
    if (!is_callable([$cache, 'getCounter'])) {
      return;
    }
    if ($expected_cache_key) {
      // Since we don't yet inject the database connection, we need to use a
      // reflection hack to set it in the plugin instance.
      $reflector = new \ReflectionObject($plugin);
      // Verify the the computed cache key.
      $property = $reflector->getProperty('cacheKey');
      $property->setAccessible(TRUE);
      $this->assertSame($expected_cache_key, $property->getValue($plugin));

      // Cache miss prior to calling ::count().
      $this->assertFalse($cache->get($expected_cache_key, 'cache'));

      $this->assertSame([], $cache->getCounter('set'));
      $count = $plugin->count();
      $this->assertSame($expected_count, $count);
      $this->assertSame([$expected_cache_key => 1], $cache->getCounter('set'));

      // Cache hit afterwards.
      $cache_item = $cache->get($expected_cache_key, 'cache');
      $this->assertNotSame(FALSE, $cache_item, 'This is not a cache hit.');
      $this->assertSame($expected_count, $cache_item->data);
    }
    else {
      $this->assertSame([], $cache->getCounter('set'));
      $plugin->count();
      $this->assertSame([], $cache->getCounter('set'));
    }

    // Test source item counts.
    if (is_null($expected_count)) {
      $expected_count = count($expected_data);
    }
    // If an expected count was given, assert it only if the plugin is
    // countable.
    if (is_numeric($expected_count)) {
      $this->assertInstanceOf('\Countable', $plugin);
      $this->assertCount($expected_count, $plugin);
    }

    $i = 0;
    $actual_data = [];
    /** @var \Drupal\migrate\Row $row */
    foreach ($plugin as $row) {
      $i++;
      $this->assertInstanceOf(Row::class, $row);

      $actual_data[] = $row->getSource();
    }

    $this->assertEquals($expected_data, $actual_data);

    // False positives occur if the foreach is not entered. So, confirm the
    // foreach loop was entered if the expected count is greater than 0.
    if ($expected_count > 0) {
      $this->assertGreaterThan(0, $i);

      // Test that we can skip all rows.
      // The 'migrate_skip_all_rows_test' test module exists and installed only
      // in Drupal core 9.1+.
      if (\Drupal::moduleHandler()->moduleExists('migrate_skip_all_rows_test')) {
        \Drupal::state()->set('migrate_skip_all_rows_test_migrate_prepare_row', TRUE);
        $iterator_items = iterator_to_array($clone_plugin, FALSE);
        $this->assertEmpty($iterator_items, 'Row not skipped');
      }
    }
  }

  /**
   * Tests the cacheability of the given source plugin.
   *
   * @param \Drupal\migrate\Plugin\MigrateSourceInterface $plugin
   *   The source plugin instance.
   * @param int $expected_count
   *   The expected source record count.
   * @param string|null $expected_cache_key
   *   The expected cache key (if any). Defaults to NULL.
   */
  protected function assertPluginCountCacheability(MigrateSourceInterface $plugin, int $expected_count, ?string $expected_cache_key) {
    /** @var \Drupal\Core\Cache\MemoryCounterBackend $cache * */
    $cache = \Drupal::cache('migrate');
    if (!is_callable([$cache, 'getCounter'])) {
      return;
    }

    if ($expected_cache_key) {
      // Since we don't yet inject the database connection, we need to use a
      // reflection hack to set it in the plugin instance.
      $reflector = new \ReflectionObject($plugin);
      // Verify the the computed cache key.
      $property = $reflector->getProperty('cacheKey');
      $property->setAccessible(TRUE);
      $this->assertSame($expected_cache_key, $property->getValue($plugin));

      // Cache miss prior to calling ::count().
      $this->assertFalse($cache->get($expected_cache_key, 'cache'));

      $this->assertSame([], $cache->getCounter('set'));
      $count = $plugin->count();
      $this->assertSame($expected_count, $count);
      $this->assertSame([$expected_cache_key => 1], $cache->getCounter('set'));

      // Cache hit afterwards.
      $cache_item = $cache->get($expected_cache_key, 'cache');
      $this->assertNotSame(FALSE, $cache_item, 'This is not a cache hit.');
      $this->assertSame($expected_count, $cache_item->data);
    }
    else {
      $this->assertSame([], $cache->getCounter('set'));
      $plugin->count();
      $this->assertSame([], $cache->getCounter('set'));
    }
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\Tests\migrate\Kernel\MigrateTestBase::cleanupMigrateConnection()
   */
  protected function tearDown(): void {
    $this->cleanupSourceMigrateConnection();
    parent::tearDown();
  }

}
