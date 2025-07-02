<?php

declare(strict_types=1);

namespace Drupal\file_mdm;

/**
 * Provides an interface for file metadata manager objects.
 */
interface FileMetadataManagerInterface extends \Countable {

  /**
   * Determines if the URI is currently in use by the manager.
   *
   * @param string $uri
   *   The URI to a file.
   *
   * @return bool
   *   TRUE if the URI is in use, FALSE otherwise.
   */
  public function has(string $uri): bool;

  /**
   * Returns a FileMetadata object for the URI, creating it if necessary.
   *
   * @param string $uri
   *   The URI to a file.
   *
   * @return \Drupal\file_mdm\FileMetadataInterface|null
   *   The FileMetadata object for the specified URI.
   */
  public function uri(string $uri): ?FileMetadataInterface;

  /**
   * Deletes the all the cached metadata for the URI.
   *
   * @param string $uri
   *   The URI to a file.
   *
   * @return bool
   *   TRUE if the cached metadata was removed, FALSE in case of error.
   */
  public function deleteCachedMetadata(string $uri): bool;

  /**
   * Releases the FileMetadata object for the URI.
   *
   * @param string $uri
   *   The URI to a file.
   *
   * @return bool
   *   TRUE if the FileMetadata for the URI was removed from the manager,
   *   FALSE otherwise.
   */
  public function release(string $uri): bool;

}
