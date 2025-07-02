<?php

declare(strict_types=1);

namespace Drupal\file_mdm;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file_mdm\Plugin\FileMetadataPluginManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * A service class to provide file metadata.
 */
class FileMetadataManager implements FileMetadataManagerInterface {

  use StringTranslationTrait;

  /**
   * The array of FileMetadata objects currently in use.
   *
   * @var \Drupal\file_mdm\FileMetadataInterface[]
   */
  protected array $files = [];

  public function __construct(
    protected readonly FileMetadataPluginManagerInterface $pluginManager,
    #[Autowire(service: 'logger.channel.file_mdm')]
    protected readonly LoggerInterface $logger,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly FileSystemInterface $fileSystem,
    #[Autowire(service: 'cache.file_mdm')]
    protected readonly CacheBackendInterface $cache,
    protected readonly StreamWrapperManagerInterface $streamWrapperManager,
  ) {}

  /**
   * Returns an hash for the URI, used internally by the manager.
   *
   * @param string $uri
   *   The URI to a file.
   *
   * @return string|null
   *   An hash string.
   */
  protected function calculateHash(string $uri): ?string {
    // Sanitize URI removing duplicate slashes, if any.
    // @see http://stackoverflow.com/questions/12494515/remove-unnecessary-slashes-from-path
    $uri = preg_replace('/([^:])(\/{2,})/', '$1/', $uri);
    // If URI is invalid and no local file path exists, return NULL.
    if (!$this->streamWrapperManager->isValidUri($uri) && !$this->fileSystem->realpath($uri)) {
      return NULL;
    }
    // Return a hash of the URI.
    return hash('sha256', $uri);
  }

  public function has(string $uri): bool {
    $hash = $this->calculateHash($uri);
    return $hash ? isset($this->files[$hash]) : FALSE;
  }

  public function uri(string $uri): ?FileMetadataInterface {
    if (!$hash = $this->calculateHash($uri)) {
      return NULL;
    }
    if (!isset($this->files[$hash])) {
      $this->files[$hash] = new FileMetadata($this->pluginManager, $this->logger, $this->fileSystem, $this->configFactory, $uri, $hash);
    }
    return $this->files[$hash];
  }

  public function deleteCachedMetadata(string $uri): bool {
    if (!$hash = $this->calculateHash($uri)) {
      return FALSE;
    }
    foreach (array_keys($this->pluginManager->getDefinitions()) as $pluginId) {
      $this->cache->delete("hash:{$pluginId}:{$hash}");
    }
    return TRUE;
  }

  public function release(string $uri): bool {
    if (!$hash = $this->calculateHash($uri)) {
      return FALSE;
    }
    if (isset($this->files[$hash])) {
      unset($this->files[$hash]);
      return TRUE;
    }
    return FALSE;
  }

  public function count(): int {
    return count($this->files);
  }

}
