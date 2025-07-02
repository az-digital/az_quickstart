<?php

declare(strict_types=1);

namespace Drupal\file_mdm;

use Drupal\file_mdm\Plugin\FileMetadataPluginInterface;

/**
 * Provides an interface for file metadata objects.
 */
interface FileMetadataInterface {

  /**
   * Metadata not loaded.
   */
  public const NOT_LOADED = 0;

  /**
   * Metadata loaded by code.
   */
  public const LOADED_BY_CODE = 1;

  /**
   * Metadata loaded from cache.
   */
  public const LOADED_FROM_CACHE = 2;

  /**
   * Metadata loaded from file.
   */
  public const LOADED_FROM_FILE = 3;

  /**
   * Gets the URI of the file.
   *
   * @return string|null
   *   The URI of the file, or a local path.
   */
  public function getUri(): ?string;

  /**
   * Gets the local filesystem URI to the temporary file.
   *
   * @return string|null
   *   The URI, or a local path, of the temporary file.
   */
  public function getLocalTempPath(): ?string;

  /**
   * Sets the local filesystem URI to the temporary file.
   *
   * @param string $tempUri
   *   A URI to a temporary file.
   *
   * @return $this
   */
  public function setLocalTempPath(string $tempUri): static;

  /**
   * Copies the file at URI to a local temporary file.
   *
   * @param string|null $tempUri
   *   (optional) a URI to a temporary file. If NULL, a temp URI will be
   *   defined by the operation. Defaults to NULL.
   *
   * @return bool
   *   TRUE if the file was copied successfully, FALSE
   *   otherwise.
   */
  public function copyUriToTemp(?string $tempUri = NULL): bool;

  /**
   * Copies the local temporary file to the destination URI.
   *
   * @return bool
   *   TRUE if the file was copied successfully, FALSE
   *   otherwise.
   */
  public function copyTempToUri(): bool;

  /**
   * Gets a FileMetadata plugin instance.
   *
   * @param string $metadataId
   *   The id of the plugin whose instance is to be returned. If it is does
   *   not exist, an instance is created.
   *
   * @return \Drupal\file_mdm\Plugin\FileMetadataPluginInterface|null
   *   The FileMetadata plugin instance. NULL if no plugin is found.
   */
  public function getFileMetadataPlugin(string $metadataId): ?FileMetadataPluginInterface;

  /**
   * Returns a list of supported metadata keys.
   *
   * @param string $metadataId
   *   The id of the FileMetadata plugin.
   * @param mixed $options
   *   (optional) Allows specifying additional options to control the list of
   *   metadata keys returned.
   *
   * @return array
   *   A simple array of metadata keys supported.
   */
  public function getSupportedKeys(string $metadataId, mixed $options = NULL): array;

  /**
   * Gets a metadata element.
   *
   * @param string $metadataId
   *   The id of the FileMetadata plugin.
   * @param mixed|null $key
   *   A key to determine the metadata element to be returned. If NULL, the
   *   entire metadata will be returned.
   *
   * @return mixed
   *   The value of the element specified by $key. If $key is NULL, the entire
   *   metadata.
   */
  public function getMetadata(string $metadataId, mixed $key = NULL): mixed;

  /**
   * Removes a metadata element.
   *
   * @param string $metadataId
   *   The id of the FileMetadata plugin.
   * @param mixed $key
   *   A key to determine the metadata element to be removed.
   *
   * @return bool
   *   TRUE if metadata was removed successfully, FALSE otherwise.
   */
  public function removeMetadata(string $metadataId, mixed $key): bool;

  /**
   * Sets a metadata element.
   *
   * @param string $metadataId
   *   The id of the FileMetadata plugin.
   * @param mixed $key
   *   A key to determine the metadata element to be changed.
   * @param mixed $value
   *   The value to change the metadata element to.
   *
   * @return bool
   *   TRUE if metadata was changed successfully, FALSE otherwise.
   */
  public function setMetadata(string $metadataId, mixed $key, mixed $value): bool;

  /**
   * Checks if file metadata has been already loaded.
   *
   * @param string $metadataId
   *   The id of the FileMetadata plugin.
   *
   * @return int|false
   *   The metadata loading status if metadata is loaded, FALSE otherwise.
   */
  public function isMetadataLoaded(string $metadataId): int|false;

  /**
   * Loads file metadata.
   *
   * @param string $metadataId
   *   The id of the FileMetadata plugin.
   * @param mixed $metadata
   *   The file metadata associated to the file at URI.
   *
   * @return bool
   *   TRUE if metadata was loaded successfully, FALSE otherwise.
   */
  public function loadMetadata(string $metadataId, mixed $metadata): bool;

  /**
   * Loads file metadata from a cache entry.
   *
   * @param string $metadataId
   *   The id of the FileMetadata plugin.
   *
   * @return bool
   *   TRUE if metadata was loaded successfully, FALSE otherwise.
   */
  public function loadMetadataFromCache(string $metadataId): bool;

  /**
   * Caches metadata for file at URI.
   *
   * Uses the 'file_mdm' cache bin.
   *
   * @param string $metadataId
   *   The id of the FileMetadata plugin.
   * @param array $tags
   *   (optional) An array of cache tags to save to cache.
   *
   * @return bool
   *   TRUE if metadata was saved successfully, FALSE otherwise.
   */
  public function saveMetadataToCache(string $metadataId, array $tags = []): bool;

  /**
   * Saves metadata to file at URI.
   *
   * @param string $metadataId
   *   The id of the FileMetadata plugin.
   *
   * @return bool
   *   TRUE if metadata was saved successfully, FALSE otherwise.
   */
  public function saveMetadataToFile(string $metadataId): bool;

}
