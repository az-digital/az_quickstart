<?php

declare(strict_types=1);

namespace Drupal\Tests\config_split\Kernel;

use Drupal\config_split\Config\ConfigSorter;
use Drupal\Core\Config\FileStorage;
use Drupal\KernelTests\KernelTestBase;

/**
 * Test sorting.
 *
 * @group config_split
 */
class ConfigSorterTest extends KernelTestBase {

  /**
   * Test sorting of config provided by the umami profile.
   */
  public function testSortingUmamiConfig() {
    // Shortcutting the installation of the umami profile.
    // We pretend that the config/install of umami is the active storage.
    $path = $this->container->get('extension.list.profile')->getPath('demo_umami');
    $storage = new FileStorage($path . '/config/install');

    // If this were a test in drupal core we would install umami and all the
    // non-test modules available and get the sorter from the container.
    $sorter = new ConfigSorter($this->container->get('config.typed'), $storage);

    // In a drupal core test we would also sort config that was not available
    // in the active storage. But we don't actually sort that config now.
    foreach ($storage->listAll() as $name) {
      $original = $storage->read($name);
      $shuffled = $this->shuffleDeep($original);
      $sorted = $sorter->sort($name, $shuffled);
      self::assertSame($original, $sorted, "$name is sorted again");
    }
  }

  /**
   * Shuffle the config to create a random order.
   *
   * @param mixed $config
   *   The config to shuffle.
   *
   * @return mixed
   *   The unordered array.
   */
  private function shuffleDeep($config) {
    if (!is_array($config) || empty($config)) {
      return $config;
    }

    $keys = array_keys($config);
    shuffle($keys);
    foreach ($keys as $key) {
      $new[$key] = $this->shuffleDeep($config[$key]);
    }

    return $new;
  }

}
