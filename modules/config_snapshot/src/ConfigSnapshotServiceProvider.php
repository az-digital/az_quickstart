<?php

namespace Drupal\config_snapshot;

use Drupal\Core\Config\BootstrapConfigStorageFactory;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Registers one service per config snapshot.
 */
class ConfigSnapshotServiceProvider extends ServiceProviderBase {

  const CONFIG_PREFIX = 'config_snapshot.snapshot.';

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    // @see Drupal\language\LanguageServiceProvider::isMultilingual()
    // @todo Try to swap out for config.storage to take advantage of database
    //   and caching. This might prove difficult as this is called before the
    //   container has finished building.
    try {
      $config_storage = BootstrapConfigStorageFactory::get();
      if (!$config_storage) {
        throw new \Exception('Failed to retrieve config storage.');
      }

      $config_ids = $config_storage->listAll(static::CONFIG_PREFIX);

      foreach ($config_ids as $config_id) {
        try {
          $snapshot = $config_storage->read($config_id);

          // Validate snapshot data before proceeding.
          if (!isset($snapshot['snapshotSet'], $snapshot['extensionType'], $snapshot['extensionName'])) {
            throw new \UnexpectedValueException("Invalid snapshot data for config ID {$config_id}");
          }

          // Proceed with the registration.
          $container->register("config_snapshot.{$snapshot['snapshotSet']}.{$snapshot['extensionType']}.{$snapshot['extensionName']}", 'Drupal\config_snapshot\ConfigSnapshotStorage')
            ->addArgument($snapshot['snapshotSet'])
            ->addArgument($snapshot['extensionType'])
            ->addArgument($snapshot['extensionName']);
        }
        catch (\Exception $e) {
          // Log error and continue with the next config_id.
          error_log("Error processing config ID {$config_id}: " . $e->getMessage());
        }
      }
    }
    catch (\Exception $e) {
      // Handle other errors that might stop the script
      // before it starts processing.
      error_log('Error initializing config storage: ' . $e->getMessage());
    }
  }

}
