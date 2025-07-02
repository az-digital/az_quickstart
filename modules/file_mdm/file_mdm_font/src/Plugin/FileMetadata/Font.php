<?php

declare(strict_types=1);

namespace Drupal\file_mdm_font\Plugin\FileMetadata;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\file_mdm\FileMetadataException;
use Drupal\file_mdm\Plugin\Attribute\FileMetadata;
use Drupal\file_mdm\Plugin\FileMetadata\FileMetadataPluginBase;
use FontLib\Font as LibFont;
use FontLib\Table\Type\name;

/**
 * FileMetadata plugin for TTF/OTF/WOFF font information.
 *
 * Uses the 'PHP Font Lib' library (dompdf/php-font-lib).
 */
#[FileMetadata(
  id: 'font',
  title: new TranslatableMarkup('Font'),
  help: new TranslatableMarkup('File metadata plugin for TTF/OTF/WOFF font information, using the PHP Font Lib.')
)]
class Font extends FileMetadataPluginBase {

  public function getSupportedKeys(array $options = NULL): array {
    return array_merge(['FontType', 'FontWeight'], array_values(name::$nameIdCodes));
  }

  protected function doGetMetadataFromFile(): mixed {
    $font = LibFont::load($this->getLocalTempPath());
    // @todo The ::parse() method raises 'Undefined offset' notices in
    //   phenx/php-font-lib 0.5, suppress errors while upstream is fixed.
    @$font->parse();
    $keys = $this->getSupportedKeys();
    $metadata = [];
    foreach ($keys as $key) {
      $l_key = strtolower($key);
      switch ($l_key) {
        case 'fonttype':
          $metadata[$l_key] = $font->getFontType();
          break;

        case 'fontweight':
          $metadata[$l_key] = $font->getFontWeight();
          break;

        default:
          $code = array_search($l_key, array_map('strtolower', name::$nameIdCodes), TRUE);
          if ($value = $font->getNameTableString($code)) {
            $metadata[$l_key] = $value;
          }
          break;

      }
    }
    return $metadata;
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
    if (!is_string($key)) {
      throw new FileMetadataException("Invalid metadata key specified", $this->getPluginId(), $method);
    }
    if (!in_array(strtolower($key), array_map('strtolower', $this->getSupportedKeys()), TRUE)) {
      throw new FileMetadataException("Invalid metadata key '{$key}' specified", $this->getPluginId(), $method);
    }
    return TRUE;
  }

  protected function doGetMetadata(mixed $key = NULL): mixed {
    if ($key === NULL) {
      return $this->metadata;
    }
    else {
      $this->validateKey($key, __FUNCTION__);
      $l_key = strtolower($key);
      if (in_array($l_key, array_map('strtolower', $this->getSupportedKeys()), TRUE)) {
        return $this->metadata[$l_key] ?? NULL;
      }
      return NULL;
    }
  }

  protected function doSetMetadata(mixed $key, mixed $value): bool {
    throw new FileMetadataException('Changing font metadata is not supported', $this->getPluginId());
  }

  protected function doRemoveMetadata(mixed $key): bool {
    throw new FileMetadataException('Deleting font metadata is not supported', $this->getPluginId());
  }

}
