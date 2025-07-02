<?php

namespace Drupal\asset_injector;

use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelTrait;

/**
 * Class AssetFileStorage.
 *
 * This asset file storage class implements a content-addressed file system
 * where each file is stored in a location like so:
 * public://asset_injector/[extension]/[name]-[md5].[extension]
 * Note that the name and extension-dir are redundant and purely for DX.
 *
 * Due to the nature of the config override system, the content of any asset
 * config entity can vary on external factory beyond our control, be it
 * language, domain, settings.php overrides or anything else. In other words,
 * any asset entity can map to an arbitrary number of actual assets.
 * Thus asset files are generated in AssetFileStorage::internalFileUri()
 * with a file name that is unique by their content, and only deleted on cache
 * flush.
 *
 * @see asset_injector_page_attachments().
 * @package Drupal\asset_injector
 */
final class AssetFileStorage {

  use LoggerChannelTrait;

  /**
   * Asset with file storage.
   *
   * @var AssetInjectorInterface
   */
  protected $asset;

  /**
   * AssetFileStorage constructor.
   *
   * @param AssetInjectorInterface $asset
   *   The asset.
   */
  public function __construct(AssetInjectorInterface $asset) {
    $this->asset = $asset;
  }

  /**
   * Get the URI where the assets will be stored.
   *
   * @return string
   *   Internal URI using either public:// or assets:// stream wrapper.
   *   assets:// was introduced in Drupal 10.1.0
   *   @see https://www.drupal.org/node/3328126
   */
  public static function getDirectoryUri(): string {
    $directory_uri = 'public://asset_injector';

    if (version_compare(\Drupal::VERSION, '10.1', '>=')) {
      $directory_uri = 'assets://asset_injector';
    }

    return $directory_uri;
  }

  /**
   * Create file and return internal uri.
   *
   * @return string
   *   Internal file URI using public:// stream wrapper.
   */
  public function createFile() {
    $internal_uri = self::internalFileUri();
    if (!is_file($internal_uri)) {
      $directory = dirname($internal_uri);
      $file_system = self::getFileSystemService();

      try {
        $file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
        $file_system->saveData($this->asset->getCode(), $internal_uri, FileExists::Replace);
      }
      catch (\Throwable $e) {
        $this->getLogger('asset_injector')
          ->error('Failed to create asset file @uri. Ensure directory permissions are correctly configured: @message', [
            '@uri' => $internal_uri,
            '@message' => $e->getMessage(),
          ]);
      }
    }
    return $internal_uri;
  }

  /**
   * Delete files for an asset.
   *
   * Yes, we can have multiple files for an asset configuration, if we have
   * overrides.
   */
  public function deleteFiles() {
    $pattern = $this->internalFileUri(TRUE);
    $paths = glob($pattern);
    foreach ($paths as $path) {
      self::getFileSystemService()->delete($path);
    }
  }

  /**
   * Create internal file URI or pattern.
   *
   * @param bool $pattern
   *   Get Pattern instead of internal file URI.
   *
   * @return string
   *   File uri.
   */
  protected function internalFileUri($pattern = FALSE) {
    $name = $this->asset->id();
    $extension = $this->asset->extension();
    $hash = $pattern ? '*' : md5($this->asset->getCode());
    $all_assets_directory = self::getDirectoryUri();
    if ($pattern) {
      // glob() does not understand stream wrappers. Sigh.
      $all_assets_directory = self::getFileSystemService()
        ->realpath($all_assets_directory);
    }
    $internal_uri = "$all_assets_directory/$extension/$name-$hash.$extension";
    return $internal_uri;
  }

  /**
   * Get the Drupal file system service.
   *
   * @return \Drupal\Core\File\FileSystemInterface
   *   File System service.
   */
  protected static function getFileSystemService() {
    return \Drupal::service('file_system');
  }

  /**
   * Delete all asset files.
   *
   * @see asset_injector_cache_flush()
   */
  public static function deleteAllFiles() {
    if (file_exists(self::getDirectoryUri())) {
      self::getFileSystemService()->deleteRecursive(self::getDirectoryUri());
    }
  }

}
