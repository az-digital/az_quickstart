<?php

namespace Drupal\ib_dam\Asset;

use Drupal\file\FileInterface;
use Drupal\ib_dam\Downloader;

/**
 * Interface LocalAssetInterface.
 *
 * Describes local asset functionality.
 *
 * @package Drupal\ib_dam\Asset
 */
interface LocalAssetInterface {

  /**
   * Get Asset Local File object.
   *
   * Can be an empty object returned by ::create() method.
   *
   * @return \Drupal\file\FileInterface
   *   The File instance.
   */
  public function localFile();

  /**
   * Setter for asset local file property.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file to set.
   *
   * @return \Drupal\ib_dam\Asset\LocalAssetInterface
   *   Returns this.
   */
  public function setLocalFile(FileInterface $file);

  /**
   * Set asset's local file uri property.
   *
   * @param string $uri
   *   The uri of the file, usually is relative url.
   */
  public function setFileUri($uri);

  /**
   * Fetch and download asset files from IB API.
   *
   * Local file and optionally thumbnail file.
   *
   * @param \Drupal\ib_dam\Downloader $downloader
   *   The Downloader service.
   * @param string $upload_dir
   *   Destination dir to upload files.
   *
   * @throws \Drupal\ib_dam\Exceptions\AssetDownloaderBadResponse
   */
  public function saveAttachments(Downloader $downloader, $upload_dir);

}
