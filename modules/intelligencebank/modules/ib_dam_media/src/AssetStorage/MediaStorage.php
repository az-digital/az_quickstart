<?php

namespace Drupal\ib_dam_media\AssetStorage;

use Drupal\ib_dam\Asset\AssetInterface;
use Drupal\ib_dam\Asset\EmbedAsset;
use Drupal\ib_dam\Asset\LocalAsset;
use Drupal\ib_dam\AssetStorage\AssetStorageInterface;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;

/**
 * Class MediaStorage.
 *
 * AssetStorage type for media.
 *
 * Knows how to save media entity:
 *  - pre-fills media source field with default values,
 *  - provides default thumbnail.
 *
 * @package Drupal\ib_dam_media\AssetStorage
 */
class MediaStorage implements AssetStorageInterface {

  /**
   * Media type suited for current asset.
   *
   * @var \Drupal\media\Entity\MediaType
   */
  private $mediaType;

  /**
   * Set media type.
   *
   * @param \Drupal\media\Entity\MediaType $type
   *   The loaded Media Type.
   *
   * @return $this
   *   Used for methods chaining.
   */
  public function setMediaType(MediaType $type) {
    $this->mediaType = $type;
    return $this;
  }

  /**
   * MediaStorage constructor.
   *
   * @param string $storage_key
   *   The storage id, consists of storage class, source type, media type id.
   *
   * @throws \Exception
   */
  public function __construct($storage_key) {
    list(, , $media_type_id) = explode(':', $storage_key);

    try {
      $media_type = \Drupal::entityTypeManager()
        ->getStorage('media_type')
        ->load($media_type_id);

      $this->mediaType = $media_type;
    }
    catch (\Exception $e) {
      throw $e;
    }
  }

  /**
   * Get a media source field name.
   *
   * @return string
   *   The media source field name.
   */
  public function getMediaFieldName() {
    $source = $this->mediaType->getSource();
    return $source->getConfiguration()['source_field'];
  }

  /**
   * Manager method to find method that pre-fills media source field.
   *
   * @param string $source_type
   *   The source type of the asset.
   *
   * @return string
   *   The concrete storage driver.
   */
  private function getDriver($source_type) {
    $driver_method = 'fileDriver';
    if (method_exists($this, "{$source_type}Driver")) {
      $driver_method = "{$source_type}Driver";
    }
    return $driver_method;
  }

  /**
   * Image pre-fill method.
   *
   * @param \Drupal\ib_dam\Asset\LocalAsset $asset
   *   The Asset.
   *
   * @return array
   *   The field values for media source field.
   */
  private function imageDriver(LocalAsset $asset) {
    return [
      'target_id' => $asset->localFile()->id(),
      'alt'       => $asset->getDescription() ?? $asset->getName(),
      'title'     => $asset->getName(),
    ];
  }

  /**
   * File pre-fill method.
   *
   * @param \Drupal\ib_dam\Asset\LocalAsset $asset
   *   The Asset.
   *
   * @return array
   *   The field values for media source field.
   */
  private function fileDriver(LocalAsset $asset) {
    return [
      'target_id'   => $asset->localFile()->id(),
      'description' => $asset->getName(),
    ];
  }

  /**
   * Embed pre-fill method.
   *
   * @param \Drupal\ib_dam\Asset\EmbedAsset $asset
   *   The Asset.
   *
   * @return array
   *   The field values for media source field.
   */
  private function embedDriver(EmbedAsset $asset) {
    return [
      'uri'   => $asset->getUrl(),
      'title' => $asset->getName(),
      'options' => [
        'attributes' => [
          'ib_dam' => [
            'asset_type'   => $asset->getType(),
            'filemimetype' => $asset->source()->getMimetype(),
            'extra'        => $asset->getDisplaySettings() + [
              'alt' => $asset->getDescription() ?? $asset->getName(),
            ],
          ]
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @uses imageDriver()
   * @uses fileDriver()
   * @uses embedDriver()
   */
  public function createStorage(AssetInterface $asset) {
    /* @var $media \Drupal\media\Entity\Media */
    $media_asset_type = $asset->getSourceType() == 'embed'
      ? $asset->getSourceType()
      : $asset->getType();

    $driver = $this->getDriver($media_asset_type);
    $source = $this->getMediaFieldName();
    $value  = $this->{$driver}($asset);

    $values = [
      'name'   => $asset->getName(),
      'bundle' => $this->mediaType->id(),
      'uid'    => $asset->getOwnerId(),
      'status' => TRUE,
      'type'   => $this->mediaType->getSource()->getPluginId(),
      $source  => $value,
    ];

    $values += ['original_item' => $asset->source()];
    $media   = Media::create($values);

    return $media;
  }

}
