<?php

namespace Drupal\ib_dam\Asset;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\ib_dam\AssetValidation\AssetViolationAggregator;
use Drupal\ib_dam\Exceptions\AssetUnableCreateStorageHandler;
use Drupal\ib_dam\Exceptions\AssetUnableSaveThumbnailFile;
use Drupal\ib_dam\IbDamResourceModel;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Class Asset.
 *
 * Base class for asset items.
 *
 * @package Drupal\ib_dam\Asset
 */
abstract class Asset implements AssetInterface {

  use StringTranslationTrait;

  protected $type;
  protected $name;
  protected $source;
  protected $storage;
  protected $storageType;
  protected $ownerId;
  protected $hasPreview;
  protected $description = NULL;

  /**
   * Asset type id.
   *
   * @var string
   */
  protected static $sourceType;
  /**
   * Asset thumbnail.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $thumbnail;

  /**
   * Factory method to build an asset from source model.
   *
   * @param \Drupal\ib_dam\IbDamResourceModel $source
   *   The source object.
   * @param string $owner_id
   *   The owner of asset that will be constructed and later might be saved.
   * @param bool $has_preview
   *   The flag to indicate if asset should be previewable,
   *   return true if should.
   *
   * @return \Drupal\ib_dam\Asset\AssetInterface
   *   Asset object.
   */
  public static function createFromSource(IbDamResourceModel $source, $owner_id, $has_preview = TRUE) {
    // We use factory to hide instantiation things for later extendability,
    // like adding asset db table and load all data from it,
    // without braking an asset creation logic.
    $type        = $source->getType();
    $name        = $source->getName();
    $description = $source->getDescription();

    if ($source->getResourceType() === 'embed') {
      $asset = new EmbedAsset($type, $name, $has_preview, $owner_id, $description);
      $asset->setUrl($source->getUrl());
    }
    else {
      $file = File::create([
        'uid'      => $owner_id,
        'status'   => 0,
        'filename' => $source->getFileName(),
        'uri'      => $source->getUrl(),
        'filemime' => $source->getMimetype(),
      ]);

      $asset = new LocalAsset($type, $name, $has_preview, $owner_id, $description);
      $asset->setLocalFile($file);
    }

    if ($has_preview) {
      $thumb = File::create([
        'uid'      => $owner_id,
        'status'   => 0,
        'filename' => '',
        'uri'      => '',
        'filemime' => $source->getMimetype(),
      ]);
      $asset->setThumbnail($thumb);
    }

    return $asset->setSource($source);
  }

  /**
   * Factory method to build an asset from array of properties.
   *
   * @param array $data
   *   The array of values used as asset properties.
   *
   * @return \Drupal\ib_dam\Asset\AssetInterface
   *   Asset object.
   */
  public static function createFromValues(array $data) {
    $asset = NULL;
    $type  = $data['type'];
    $name  = $data['name'];
    $desc  = $data['description'] ?? '';
    $has_preview = !isset($data['has_preview']) ?: $data['has_preview'];

    if (!empty($data['remote_url'])) {
      // Where do we get owner id: from node, local file,
      // do we need owner at all?
      // $owner_id = $data['getContainer()->getOwnerId();
      $asset = new EmbedAsset($type, $name, $has_preview, $owner_id = 0, $desc);
      $asset->setUrl($data['remote_url']);
    }
    else {
      if (!empty($data['file_id'])) {
        $file = File::load($data['file_id']);
        $owner_id = $file->getOwnerId();
        $asset = new LocalAsset($type, $name, $has_preview, $owner_id, $desc);
        $asset->setLocalFile($file);
      }
    }
    return $asset;
  }

  /**
   * Asset constructor.
   *
   * @param string $type
   *   The asset type, eg. image, video, audio, file.
   * @param string $name
   *   The asset name get from api response.
   * @param bool $has_preview
   *   The flag to indicate if asset should be previewable,
   *   return true if should.
   * @param string $owner_id
   *   The owner of asset that will be constructed and later might be saved.
   * @param string $description
   *    The asset's description text.
   */
  public function __construct($type, $name, $has_preview, $owner_id, $description = NULL) {
    $this->type    = $type;
    $this->name    = $name;
    $this->ownerId = $owner_id;
    $this->hasPreview = (bool) $has_preview;
    $this->description = !empty($description) && $description != '' ? $description : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceType() {
    return static::$sourceType;
  }

  /**
   * {@inheritdoc}
   */
  public function setStorageType($storage_type_id) {
    $this->storageType = $storage_type_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStorageType() {
    return $this->storageType;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($text = '') {
    $this->name = $text;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($text = '') {
    $this->description = $text;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function source() {
    return $this->source;
  }

  /**
   * {@inheritdoc}
   */
  public function setSource(IbDamResourceModel $model) {
    $this->source = $model;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function thumbnail() {
    return $this->thumbnail;
  }

  /**
   * {@inheritdoc}
   */
  public function setThumbnail(FileInterface $thumbnail = NULL) {
    $this->thumbnail = $thumbnail;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setThumbUri($uri) {
    $this->thumbnail->setFileUri($uri);
  }

  /**
   * {@inheritdoc}
   */
  public function hasPreview() {
    return $this->hasPreview;
  }

  /**
   * {@inheritdoc}
   */
  public function setHasPreview($status) {
    $this->hasPreview = (bool) $status;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->ownerId;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    if ($this->hasPreview()) {
      $thumb_errors = $this->validateThumbnail($this->thumbnail());

      if (!$thumb_errors) {
        try {
          $this->thumbnail()->save();
        }
        catch (EntityStorageException $e) {
          (new AssetUnableSaveThumbnailFile($e->getMessage()))
            ->logException()
            ->displayMessage();
        }
      }
      else {
        $error_messages = AssetViolationAggregator::extractMessages($thumb_errors);

        (new AssetUnableSaveThumbnailFile($error_messages))
          ->logException()
          ->displayMessage();
      }
    }

    return $this->createStorage();
  }

  /**
   * Get storage handler and create object that suitable for storing in db.
   *
   * @return mixed
   *   Storage item, can be array, string, object.
   */
  protected function createStorage() {
    list($storage_class) = explode(':', $this->storageType);

    /* @var $storage_handler \Drupal\ib_dam\AssetStorage\AssetStorageInterface */
    try {
      $storage_handler = new $storage_class($this->storageType);
    }
    catch (\Exception $e) {
      (new AssetUnableCreateStorageHandler($e->getMessage()))->logException();
      return FALSE;
    }
    return $storage_handler->createStorage($this);
  }

  /**
   * Validate thumbnail file using typed data.
   *
   * @param null|FileInterface $file
   *   The File instance.
   *
   * @return null|\Symfony\Component\Validator\ConstraintViolationList
   *   The list of constraint violations for the given file.
   */
  protected function validateThumbnail(FileInterface $file = NULL) {
    $violations = new ConstraintViolationList();

    // @todo: ask asset storage if we need validate thumbnail.
    // Because thumbnail for embed media should be provided
    // directly from IB CDN.
    // $this->getStorageType()->needsPreview()?
    // or better to place it in client call $this->save().
    if (!$file instanceof FileInterface) {
      $error = $this->t("Asset thumbnail file isn't a File entity");
      $violations->add(
        new ConstraintViolation($error, $error, [], $file, '', $file)
      );
    }
    else {
      $violations->addAll($file->validate());
    }

    return $violations->count() > 0
      ? $violations
      : NULL;
  }

}
