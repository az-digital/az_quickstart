<?php

namespace Drupal\ib_dam\Asset;

use Drupal\file\FileInterface;
use Drupal\ib_dam\IbDamResourceModel;

/**
 * Interface AssetInterface.
 *
 * Describes Asset structure and behaviours.
 *
 * @package Drupal\ib_dam\Asset
 */
interface AssetInterface {

  /**
   * Set storage handler.
   *
   * @param string $storage_type_id
   *   The storage handler id.
   *
   * @return \Drupal\ib_dam\Asset\Asset
   *   Return asset.
   */
  public function setStorageType($storage_type_id);

  /**
   * Returns storage type key.
   *
   * Used to get key for logging purpose,
   * as well as for creating an instance of the asset storage class.
   *
   * @return null|string
   *   The storage type key.
   *   Should contains at least:
   *     - asset storage class name,
   *     - source type: (file, image, etc.).
   *   Example: Drupal\ib_dam_media\AssetStorage\MediaStorage:file:local_file.
   */
  public function getStorageType();

  /**
   * Returns a list of applicable asset validators for a given asset type.
   *
   * @return array
   *   The asset's validators list.
   */
  public static function getApplicableValidators();

  /**
   * Build object that ready to store or process somewhere.
   *
   * This method designed to get final representation of an asset.
   * You can build in this method a textual representation of an asset,
   * or create some entity type that used as asset storage container.
   *
   * @return mixed
   *   Ready to save or process asset storage item.
   */
  public function save();

  /**
   * Returns asset's name.
   */
  public function getName();

  /**
   * Setter for asset name property.
   *
   * @param string $text
   *   Description text.
   *
   * @return \Drupal\ib_dam\Asset\AssetInterface
   *   Return $this.
   */
  public function setName($text = '');

  /**
   * Returns asset's description.
   */
  public function getDescription();

  /**
   * Setter for asset description property.
   *
   * @param string $text
   *   Description text.
   *
   * @return \Drupal\ib_dam\Asset\AssetInterface
   *   Return $this.
   */
  public function setDescription($text = '');

  /**
   * Returns asset's type.
   */
  public function getType();

  /**
   * Returns asset's owner user id.
   */
  public function getOwnerId();

  /**
   * Wrapper to set up source thumbnail file uri.
   */
  public function setThumbUri($uri);

  /**
   * Returns asset's thumbnail file object.
   *
   * @return \Drupal\file\FileInterface
   *   The thumbnail file object.
   */
  public function thumbnail();

  /**
   * Indicate whether asset should has preview.
   *
   * @return bool
   *   Return true when it should.
   */
  public function hasPreview();

  /**
   * Setter for hasPreview asset property.
   *
   * @param string $status
   *   Use true when asset should be previewable.
   *
   * @return \Drupal\ib_dam\Asset\AssetInterface
   *   Return $this.
   */
  public function setHasPreview($status);

  /**
   * Returns asset source type.
   *
   * Basically it should got from source model object.
   */
  public function getSourceType();

  /**
   * Returns asset's source model object.
   *
   * @return \Drupal\ib_dam\IbDamResourceModel
   *   The source model object.
   */
  public function source();

  /**
   * Setter for asset source property.
   *
   * @param \Drupal\ib_dam\IbDamResourceModel $model
   *   Response model.
   *
   * @return \Drupal\ib_dam\Asset\AssetInterface
   *   Return $this.
   */
  public function setSource(IbDamResourceModel $model);

  /**
   * Setter for thumbnail property.
   *
   * @param \Drupal\file\FileInterface|null $thumbnail
   *   The File instance.
   *
   * @return \Drupal\ib_dam\Asset\AssetInterface
   *   Return this.
   */
  public function setThumbnail(FileInterface $thumbnail = NULL);

}
