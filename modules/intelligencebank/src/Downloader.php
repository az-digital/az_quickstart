<?php

namespace Drupal\ib_dam;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\FileInterface;
use Drupal\ib_dam\Asset\AssetInterface;
use Drupal\ib_dam\Exceptions\AssetDownloaderBadDestination;
use Drupal\ib_dam\Exceptions\AssetDownloaderBadResponse;
use Psr\Http\Message\ResponseInterface;

/**
 * Downloader service.
 *
 * Used to download asset files and thumbnails using IntelligenceBank DAM API.
 *
 * @package Drupal\ib_dam
 */
class Downloader {

  use StringTranslationTrait;

  const THUMBNAIL_BASE_URL = 'https://apius.intelligencebank.com/webapp/1.0/icon';

  protected $uuidComponent;
  protected $fileSystem;
  protected $logger;
  protected $config;
  protected $api;

  /**
   * Constructs Downloader object.
   */
  public function __construct(
    IbDamApi $api,
    ConfigFactoryInterface $config_factory,
    LoggerChannelInterface $logger_chanel,
    FileSystemInterface $file_system,
    UuidInterface $uuid_component
  ) {
    $this->api = $api;
    $this->config = $config_factory->get('id_dam.settings');
    $this->logger = $logger_chanel;
    $this->fileSystem = $file_system;
    $this->uuidComponent = $uuid_component;
  }

  /**
   * Download asset file.
   *
   * Fetch file stream from api and save as unmanaged local file.
   *
   * @param \Drupal\ib_dam\Asset\AssetInterface $asset
   *   The asset object where take resource url.
   * @param string $upload_dir
   *   The file dir uri where store unmanaged file.
   *
   * @return bool|null
   *   Result of download operation.
   */
  public function download(AssetInterface $asset, $upload_dir) {
    $asset_source = $asset->source();
    $response     = $this->api
      ->setSessionId($asset_source->getSessionId())
      ->fetchResource($asset_source->getUrl());

    if (!$response instanceof ResponseInterface) {
      (new AssetDownloaderBadResponse())->logException()
        ->displayMessage();
      return FALSE;
    }

    try {
      $status = $this->saveUnmanagedFile(
        $response,
        $upload_dir,
        $asset_source->getFileName()
      );
    }
    catch (AssetDownloaderBadDestination $e) {
      $e->logException()->displayMessage();
      return FALSE;
    }
    catch (AssetDownloaderBadResponse $e) {
      $e->logException()->displayMessage();
      return FALSE;
    }

    return $status;
  }

  /**
   * Set correct file permissions.
   */
  public function setFilePermission(FileInterface $file) {
    $this->fileSystem->chmod($file->getFileUri());
  }

  /**
   * Fetch asset thumbnail file and save as umnanaged local file.
   *
   * @param \Drupal\ib_dam\Asset\AssetInterface $asset
   *   The asset object where take thumbnail remote url.
   * @param string $upload_dir
   *   The file dir uri where store unmanaged file.
   *
   * @return string|false
   *   Result of download operation.
   */
  public function downloadThumbnail(AssetInterface $asset, string $upload_dir) {
    $thumb_uri = $asset->source()->getThumbnail();
    $response = $this->api
      ->setSessionId($asset->source()->getSessionId())
      ->fetchResource($thumb_uri, FALSE);

    $extension     = 'png';
    $discrete_type = 'image';

    if ($response && $response->hasHeader('Content-Type')) {
      $content_type = $response->getHeader('Content-Type');
      $mimetype = reset($content_type);
      [, $extension] = explode('/', $mimetype, 2);
      $discrete_type = static::getSourceTypeFromMime($mimetype);
    }

    $guid = $this->uuidComponent->generate();
    $filename = "ib_thumb_$guid.$extension";

    // Fallback to local icon file used as default thumbnail.
    if (!$response instanceof ResponseInterface) {
      return $this->copyLocalThumbnailFile($upload_dir, $filename);
    }

    $result = FALSE;

    if ($discrete_type == 'image') {
      try {
        $result = $this->saveUnmanagedFile($response, $upload_dir, $filename);
      }
      catch (AssetDownloaderBadDestination $e) {
        $e->logException()->displayMessage();
        return FALSE;
      }
      catch (AssetDownloaderBadResponse $e) {
        $e->logException()->displayMessage();
        return FALSE;
      }
    }

    return $result;
  }

  /**
   * Helper function to prepare file directory and save upload.
   *
   * Fetch file data from HTTP stream.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The HTTP response object.
   * @param string $directory
   *   The upload directory path.
   * @param string $filename
   *   The file name of file that will be saved.
   *
   * @return string|false
   *   Result of save operation.
   *
   * @throws \Drupal\ib_dam\Exceptions\AssetDownloaderBadDestination
   * @throws \Drupal\ib_dam\Exceptions\AssetDownloaderBadResponse
   */
  private function saveUnmanagedFile(ResponseInterface $response, string $directory, string $filename) {
    $stream_data = $response->getBody();

    if (substr($directory, -1) != '/') {
      $directory .= '/';
    }
    $destination = $this->fileSystem->getDestinationFilename($directory . $filename, FileExists::Rename);

    if (!$destination) {
      throw new AssetDownloaderBadDestination($directory, $filename);
    }

    try {
      $status = $this->fileSystem->saveData((string) $stream_data, $destination);
    }
    catch (\Exception $e) {
      throw new AssetDownloaderBadResponse($e->getMessage());
    }

    if (!$status) {
      throw new AssetDownloaderBadDestination($directory, $filename);
    }
    return $status;
  }

  /**
   * Helper function to copy default local thumbnail file as media thumbnail.
   *
   * Copy logo file as .
   *
   * @param string $directory
   *   The upload directory path.
   * @param string $filename
   *   The file name of file that will be saved.
   *
   * @return string|false
   *   The Url of copied file.
   *
   * @throws \Drupal\ib_dam\Exceptions\AssetDownloaderBadDestination
   * @throws \Drupal\ib_dam\Exceptions\AssetDownloaderBadResponse
   */
  private function copyLocalThumbnailFile(string $directory, string $filename): bool|string {
    $logo_path = \Drupal::moduleHandler()->getModule('ib_dam')->getPath() . '/logo.png';

    if (substr($directory, -1) != '/') {
      $directory .= '/';
    }
    $destination = $this->fileSystem->getDestinationFilename($directory . $filename, FileExists::Rename);

    if (!$destination) {
      throw new AssetDownloaderBadDestination($directory, $filename);
    }

    try {
      $uri = $this->fileSystem->copy($logo_path, $destination);
    }
    catch (\Exception $e) {
      throw new AssetDownloaderBadResponse($e->getMessage());
    }

    if (!$uri) {
      throw new AssetDownloaderBadDestination($directory, $filename);
    }
    return $uri;
  }

  /**
   * Useful helper function to get "right" asset type from mimetype.
   *
   * Some of resources aren't as they should be,
   * for example image/vnd.photoshop.. isn't image
   * that can be easy rendered in a site.
   * The same thing for svg files, it's rather file than image.
   *
   * Also some image isn't supported by current site image toolkit.
   */
  public static function getSourceTypeFromMime($mime) {
    $image_factory = \Drupal::service('image.factory');
    $supported_image_types = $image_factory->getSupportedExtensions();

    [$type, $subtype] = explode('/', $mime);

    $asset_type = $type;

    if ($type === 'image') {
      if (strpos('vnd', $subtype) !== FALSE) {
        $asset_type = 'file';
      }
      else {
        switch ($subtype) {

          case 'webp':
          case 'svg+xml':
            $asset_type = 'file';
            break;

          default:
            $asset_type = in_array($subtype, $supported_image_types)
              ? 'image'
              : 'file';
            break;
        }
      }
    }
    elseif ($type === 'application') {
      $asset_type = 'file';
    }
    return $asset_type;
  }

}
