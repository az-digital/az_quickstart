<?php

namespace Drupal\ib_dam\Asset;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\file\FileInterface;
use Drupal\ib_dam\AssetValidation\AssetViolationAggregator;
use Drupal\ib_dam\Downloader;
use Drupal\ib_dam\Exceptions\AssetUnableSaveLocalFile;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Class LocalAsset.
 *
 * Holds logic for local asset type.
 *
 * @package Drupal\ib_dam\Asset
 */
class LocalAsset extends Asset implements LocalAssetInterface {

  /**
   * Asset type.
   *
   * @var string
   */
  protected static $sourceType = 'local';

  /**
   * Asset file.
   *
   * @var \Drupal\file\FileInterface
   */
  private $localFile;

  /**
   * {@inheritdoc}
   */
  public function localFile() {
    return $this->localFile;
  }

  /**
   * {@inheritdoc}
   */
  public function setLocalFile(FileInterface $file) {
    $this->localFile = $file;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setFileUri($uri) {
    $this->localFile->setFileUri($uri);
  }

  /**
   * {@inheritdoc}
   */
  public static function getApplicableValidators() {
    return [
      'validateFileExtensions',
      'validateFileDirectory',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $file  = $this->localFile();

    $file_errors = $this->validateFile($file);

    if ($file_errors) {
      $error_messages = AssetViolationAggregator::extractMessages($file_errors);
      (new AssetUnableSaveLocalFile($error_messages))
        ->logException()
        ->displayMessage();

      return FALSE;
    }

    try {
      $file->save();
    }
    catch (EntityStorageException $e) {
      (new AssetUnableSaveLocalFile($e->getMessage()))->logException();
      return FALSE;
    }
    return parent::save();
  }

  /**
   * {@inheritdoc}
   */
  public function saveAttachments(Downloader $downloader, $upload_dir) {
    $this->setFileUri(
      $downloader->download($this, $upload_dir)
    );

    if ($this->localFile->getFileUri()) {
      $downloader->setFilePermission($this->localFile);
    }

    // Download only when asset declares that it needs thumbnail.
    if ($this->hasPreview()) {
      $this->setThumbUri(
        $downloader->downloadThumbnail($this, $upload_dir)
      );

      if (!empty($this->thumbnail->getFileUri())) {
        $path = explode('/', $this->thumbnail->getFileUri());
        $filename = end($path);
        $this->thumbnail()->setFilename($filename);
        $downloader->setFilePermission($this->thumbnail);
      }
    }
  }

  /**
   * Validate file using typed data.
   *
   * @param null|FileInterface $file
   *   The File instance.
   *
   * @return null|\Symfony\Component\Validator\ConstraintViolationList
   *   The list of constraint violations for the given file.
   */
  protected function validateFile(FileInterface $file = NULL) {
    $violations = new ConstraintViolationList();

    if (!$file instanceof FileInterface) {
      $error = $this->t("Asset file isn't a File entity");
      $violations->add(
        new ConstraintViolation($error, $error, [], $file, '', $file)
      );
    }

    $violations->addAll($file->validate());
    return $violations->count() > 0
      ? $violations
      : NULL;
  }

}
