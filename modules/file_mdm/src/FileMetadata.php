<?php

declare(strict_types=1);

namespace Drupal\file_mdm;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\Exception\FileNotExistsException;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file_mdm\Plugin\FileMetadataPluginInterface;
use Drupal\file_mdm\Plugin\FileMetadataPluginManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * A file metadata object.
 */
class FileMetadata implements FileMetadataInterface {

  /**
   * The local filesystem path to the file.
   *
   * This is used to allow accessing local copies of files stored remotely, to
   * minimise remote calls and allow functions that cannot access remote stream
   * wrappers to operate locally.
   */
  protected ?string $localTempPath = NULL;

  /**
   * The array of FileMetadata plugins for this URI.
   *
   * @var \Drupal\file_mdm\Plugin\FileMetadataPluginInterface[]
   */
  protected array $plugins = [];

  /**
   * @param string|null $uri
   *   The URI of the file.
   * @param string $hash
   *   The hash used to reference the URI by file_mdm.
   */
  public function __construct(
    protected readonly FileMetadataPluginManagerInterface $pluginManager,
    protected readonly LoggerInterface $logger,
    protected readonly FileSystemInterface $fileSystem,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly ?string $uri,
    protected readonly string $hash,
  ) {}

  public function getUri(): ?string {
    return $this->uri;
  }

  public function getLocalTempPath(): ?string {
    return $this->localTempPath;
  }

  public function setLocalTempPath(string $tempUri): static {
    $this->localTempPath = $tempUri;
    foreach ($this->plugins as $plugin) {
      $plugin->setLocalTempPath($this->localTempPath);
    }
    return $this;
  }

  public function copyUriToTemp(?string $tempUri = NULL): bool {
    if ($tempUri === NULL) {
      $tempUri = $this->fileSystem->tempnam('temporary://', 'file_mdm_');
      $this->fileSystem->unlink($tempUri);
      $tempUri .= '.' . pathinfo($this->getUri(), PATHINFO_EXTENSION);
    }
    if ($tempPath = $this->fileSystem->copy($this->getUri(), $this->fileSystem->realpath($tempUri), FileExists::Replace)) {
      $this->setLocalTempPath($tempPath);
    }
    return (bool) $tempPath;
  }

  public function copyTempToUri(): bool {
    if (($tempPath = $this->getLocalTempPath()) === NULL) {
      return FALSE;
    }
    return (bool) $this->fileSystem->copy($tempPath, $this->getUri(), FileExists::Replace);
  }

  public function getFileMetadataPlugin(string $metadataId): ?FileMetadataPluginInterface {
    if (!isset($this->plugins[$metadataId])) {
      try {
        $this->plugins[$metadataId] = $this->pluginManager->createInstance($metadataId);
        $this->plugins[$metadataId]->setUri($this->uri);
        $this->plugins[$metadataId]->setLocalTempPath($this->localTempPath ?: $this->uri);
        $this->plugins[$metadataId]->setHash($this->hash);
      }
      catch (PluginNotFoundException $e) {
        return NULL;
      }
    }
    return $this->plugins[$metadataId];
  }

  public function getSupportedKeys(string $metadataId, mixed $options = NULL): array {
    try {
      if ($plugin = $this->getFileMetadataPlugin($metadataId)) {
        $keys = $plugin->getSupportedKeys($options);
      }
      else {
        $keys = [];
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error getting supported keys for @metadata metadata for @uri. Message: @message', [
        '@metadata' => $metadataId,
        '@uri' => $this->uri ?? '',
        '@message' => $e->getMessage(),
      ]);
      $keys = [];
    }
    return $keys;
  }

  public function getMetadata(string $metadataId, mixed $key = NULL): mixed {
    try {
      if ($plugin = $this->getFileMetadataPlugin($metadataId)) {
        $metadata = $plugin->getMetadata($key);
      }
      else {
        $metadata = NULL;
      }
    }
    catch (FileNotExistsException $e) {
      $logLevel = $this->configFactory
        ->get('file_mdm.settings')
        ->get('missing_file_log_level');
      if ($logLevel > -1) {
        $this->logger->log($logLevel, $e->getMessage());
      }
      $metadata = NULL;
    }
    catch (\Exception $e) {
      $this->logger->error('Error getting @metadata_id@key metadata for @uri. Message: @message', [
        '@metadata_id' => $metadataId,
        '@key' => $key ? ' (' . var_export($key, TRUE) . ')' : '',
        '@uri' => $this->uri ?? '',
        '@message' => $e->getMessage(),
      ]);
      $metadata = NULL;
    }
    return $metadata;
  }

  public function removeMetadata(string $metadataId, mixed $key): bool {
    try {
      if ($plugin = $this->getFileMetadataPlugin($metadataId)) {
        return $plugin->removeMetadata($key);
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error deleting @key from @metadata_id metadata for @uri. Message: @message', [
        '@metadata_id' => $metadataId,
        '@key' => $key ? var_export($key, TRUE) : '',
        '@uri' => $this->uri ?? '',
        '@message' => $e->getMessage(),
      ]);
    }
    return FALSE;
  }

  public function setMetadata(string $metadataId, mixed $key, mixed $value): bool {
    try {
      if ($plugin = $this->getFileMetadataPlugin($metadataId)) {
        return $plugin->setMetadata($key, $value);
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error setting @metadata_id@key metadata for @uri. Message: @message', [
        '@metadata_id' => $metadataId,
        '@key' => $key ? ' (' . var_export($key, TRUE) . ')' : '',
        '@uri' => $this->uri ?? '',
        '@message' => $e->getMessage(),
      ]);
    }
    return FALSE;
  }

  public function isMetadataLoaded(string $metadataId): int|false {
    $plugin = $this->getFileMetadataPlugin($metadataId);
    return $plugin ? $plugin->isMetadataLoaded() : FALSE;
  }

  public function loadMetadata(string $metadataId, mixed $metadata): bool {
    $plugin = $this->getFileMetadataPlugin($metadataId);
    return $plugin ? $plugin->loadMetadata($metadata) : FALSE;
  }

  public function loadMetadataFromCache(string $metadataId): bool {
    $plugin = $this->getFileMetadataPlugin($metadataId);
    return $plugin ? $plugin->loadMetadataFromCache() : FALSE;
  }

  public function saveMetadataToCache(string $metadataId, array $tags = []): bool {
    $plugin = $this->getFileMetadataPlugin($metadataId);
    return $plugin ? $plugin->saveMetadataToCache($tags) : FALSE;
  }

  public function saveMetadataToFile(string $metadataId): bool {
    $plugin = $this->getFileMetadataPlugin($metadataId);
    return $plugin ? $plugin->saveMetadataToFile() : FALSE;
  }

}
