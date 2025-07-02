<?php

declare(strict_types=1);

namespace Drupal\file_mdm\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides an interface defining a FileMetadata plugin.
 */
interface FileMetadataPluginInterface extends ContainerFactoryPluginInterface, PluginInspectionInterface, PluginFormInterface {

  /**
   * Gets default configuration for this plugin.
   *
   * @return array
   *   An associative array with the default configuration.
   */
  public static function defaultConfiguration(): array;

  /**
   * Sets the URI of the file.
   *
   * @param string $uri
   *   A URI.
   *
   * @return $this
   *
   * @throws \Drupal\file_mdm\FileMetadataException
   *   If no URI is specified.
   */
  public function setUri(string $uri): static;

  /**
   * Gets the URI of the file.
   *
   * @return string
   *   The URI of the file.
   */
  public function getUri(): string;

  /**
   * Sets the local filesystem path to the file.
   *
   * This is used to allow accessing local copies of files stored remotely, to
   * minimise remote calls and allow functions that cannot access remote stream
   * wrappers to operate locally.
   *
   * @param string $temp_path
   *   A filesystem path.
   *
   * @return $this
   */
  public function setLocalTempPath(string $temp_path): static;

  /**
   * Gets the local filesystem path to the file.
   *
   * This is used to allow accessing local copies of files stored remotely, to
   * minimise remote calls and allow functions that cannot access remote stream
   * wrappers to operate locally.
   *
   * @return string
   *   The local filesystem path to the file.
   */
  public function getLocalTempPath(): string;

  /**
   * Sets the hash used to reference the URI by the metadata manager.
   *
   * @param string $hash
   *   A hash string.
   *
   * @return $this
   *
   * @throws \Drupal\file_mdm\FileMetadataException
   *   If no hash is specified.
   */
  public function setHash(string $hash): static;

  /**
   * Returns a list of metadata keys supported by the plugin.
   *
   * @param array $options
   *   (optional) Allows specifying additional options to control the list of
   *   metadata keys returned.
   *
   * @return array
   *   A simple array of metadata keys supported.
   */
  public function getSupportedKeys(array $options = NULL): array;

  /**
   * Checks if file metadata has been already loaded.
   *
   * @return bool
   *   TRUE if metadata is loaded, FALSE otherwise.
   */
  public function isMetadataLoaded(): int|bool;

  /**
   * Loads file metadata from an in-memory object/array.
   *
   * @param mixed $metadata
   *   The file metadata associated to the file at URI.
   *
   * @return bool
   *   TRUE if metadata was loaded successfully, FALSE otherwise.
   */
  public function loadMetadata(mixed $metadata): bool;

  /**
   * Loads file metadata from the file at URI/local path.
   *
   * @return bool
   *   TRUE if metadata was loaded successfully, FALSE otherwise.
   *
   * @throws \Drupal\file_mdm\FileMetadataException
   *   In case there were significant errors reading from file.
   */
  public function loadMetadataFromFile(): bool;

  /**
   * Loads file metadata from a cache entry.
   *
   * @return bool
   *   TRUE if metadata was loaded successfully, FALSE otherwise.
   *
   * @throws \Drupal\file_mdm\FileMetadataException
   *   In case of significant errors.
   */
  public function loadMetadataFromCache(): bool;

  /**
   * Gets a metadata element.
   *
   * @param mixed $key
   *   A key to determine the metadata element to be returned. If NULL, the
   *   entire metadata will be returned.
   *
   * @return mixed
   *   The value of the element specified by $key. If $key is NULL, the entire
   *   metadata.
   */
  public function getMetadata(mixed $key = NULL): mixed;

  /**
   * Sets a metadata element.
   *
   * @param mixed $key
   *   A key to determine the metadata element to be changed.
   * @param mixed $value
   *   The value to change the metadata element to.
   *
   * @return bool
   *   TRUE if metadata was changed successfully, FALSE otherwise.
   */
  public function setMetadata(mixed $key, mixed $value): bool;

  /**
   * Removes a metadata element.
   *
   * @param mixed $key
   *   A key to determine the metadata element to be removed.
   *
   * @return bool
   *   TRUE if metadata was removed successfully, FALSE otherwise.
   */
  public function removeMetadata(mixed $key): bool;

  /**
   * Determines if plugin is capable of writing metadata to files.
   *
   * @return bool
   *   TRUE if plugin can save data to files, FALSE otherwise.
   */
  public function isSaveToFileSupported(): bool;

  /**
   * Saves metadata to file at URI.
   *
   * @return bool
   *   TRUE if metadata was saved successfully, FALSE otherwise.
   */
  public function saveMetadataToFile(): bool;

  /**
   * Caches metadata for file at URI.
   *
   * Uses the 'file_mdm' cache bin.
   *
   * @param array $tags
   *   (optional) An array of cache tags to save to cache.
   *
   * @return bool
   *   TRUE if metadata was saved successfully, FALSE otherwise.
   */
  public function saveMetadataToCache(array $tags = []): bool;

  /**
   * Removes cached metadata for file at URI.
   *
   * Uses the 'file_mdm' cache bin.
   *
   * @return bool
   *   TRUE if metadata was removed, FALSE otherwise.
   */
  public function deleteCachedMetadata(): bool;

}
