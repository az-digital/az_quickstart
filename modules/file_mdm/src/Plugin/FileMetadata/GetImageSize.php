<?php

declare(strict_types=1);

namespace Drupal\file_mdm\Plugin\FileMetadata;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\file_mdm\FileMetadataException;
use Drupal\file_mdm\Plugin\Attribute\FileMetadata;

/**
 * FileMetadata plugin for getimagesize.
 */
#[FileMetadata(
  id: 'getimagesize',
  title: new TranslatableMarkup('Getimagesize'),
  help: new TranslatableMarkup('File metadata plugin for PHP getimagesize().')
)]
class GetImageSize extends FileMetadataPluginBase {

  public function getSupportedKeys(array $options = NULL): array {
    return [0, 1, 2, 3, 'mime', 'channels', 'bits'];
  }

  protected function doGetMetadataFromFile(): mixed {
    if ($data = @getimagesize($this->getLocalTempPath())) {
      return $data;
    }
    else {
      return NULL;
    }
  }

  /**
   * Validates a file metadata key.
   *
   * @return bool
   *   TRUE if the key is valid.
   *
   * @throws \Drupal\file_mdm\FileMetadataException
   *   In case the key is invalid.
   */
  protected function validateKey(mixed $key, string $method): bool {
    if (!in_array($key, $this->getSupportedKeys(), TRUE)) {
      throw new FileMetadataException(sprintf("Invalid metadata key '%s' specified", var_export($key, TRUE)), $this->getPluginId(), $method);
    }
    return TRUE;
  }

  protected function doGetMetadata(mixed $key = NULL): mixed {
    if ($key === NULL) {
      return $this->metadata;
    }
    else {
      $this->validateKey($key, __FUNCTION__);
      return $this->metadata[$key] ?? NULL;
    }
  }

  protected function doSetMetadata(mixed $key, mixed $value): bool {
    $this->validateKey($key, __FUNCTION__);
    $this->metadata[$key] = $value;
    return TRUE;
  }

  protected function doRemoveMetadata(mixed $key): bool {
    $this->validateKey($key, __FUNCTION__);
    if (isset($this->metadata[$key])) {
      unset($this->metadata[$key]);
    }
    return TRUE;
  }

}
