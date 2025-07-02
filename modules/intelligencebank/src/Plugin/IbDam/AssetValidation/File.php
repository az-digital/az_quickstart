<?php

namespace Drupal\ib_dam\Plugin\IbDam\AssetValidation;

use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\file\Validation\FileValidatorInterface;
use Drupal\ib_dam\Asset\LocalAsset;
use Drupal\ib_dam\AssetValidation\AssetValidationBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Validates a file based on passed validators.
 *
 * @IbDamAssetValidation(
 *   id = "file",
 *   label = @Translation("File validator")
 * )
 *
 * @package Drupal\ib_dam\Plugin\ibDam\AssetValidation
 */
class File extends AssetValidationBase {

  protected $fileSystem;
  protected $streamWrapperManager;


  /**
   * File validator.
   *
   * @var \Drupal\file\Validation\FileValidatorInterface
   */
  protected FileValidatorInterface $fileValidator;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    TypedDataManagerInterface $typed_data_manager,
    FileSystemInterface $file_system,
    StreamWrapperManagerInterface $stream_wrapper_manager,
    FileValidatorInterface $fileValidator
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $typed_data_manager);
    $this->fileSystem = $file_system;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->fileValidator = $fileValidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('typed_data_manager'),
      $container->get('file_system'),
      $container->get('stream_wrapper_manager'),
      $container->get('file.validator')
    );
  }

  /**
   * File extensions validator.
   *
   * @param \Drupal\ib_dam\Asset\LocalAsset $asset
   *   The asset object to validate.
   * @param array|string $extensions
   *   The list of allowed file extensions.
   *
   * @return array
   *   An array with validation messages,
   *   that will return file_validate_extensions().
   */
  public function validateFileExtensions(LocalAsset $asset, $extensions): array {
    if (is_array($extensions)) {
      $extensions = implode(' ', $extensions);
    }

    $validators = [
      'FileExtension' => [
        'extensions' => $extensions,
      ],
    ];
    $violations = $this->fileValidator->validate($asset->localFile(), $validators);
    $actual_errors = [];
    foreach ($violations as $violation) {
      $actual_errors[] = $violation->getMessage();
    }

    return $actual_errors;
  }

  /**
   * File directory validator.
   *
   * @param \Drupal\ib_dam\Asset\LocalAsset $asset
   *   The asset object to validate.
   * @param string $file_dir
   *   The file directory to check.
   *
   * @return array
   *   An array with validation messages
   */
  public function validateFileDirectory(LocalAsset $asset, $file_dir) {
    $errors = [];
    $filename = $asset->localFile()->getFilename();
    $bad_dir = $this->t('This file can not be uploaded to the directory %dir.', ['%dir' => $file_dir]);

    $destination_scheme = $this->getScheme($file_dir);
    if (!$this->isValidScheme($destination_scheme)) {
      $errors[] = $bad_dir;
      return $errors;
    }

    // Prepare the destination dir.
    if (!file_exists($file_dir)) {
      $this->fileSystem->mkdir($file_dir, NULL, TRUE);
    }

    // A file URI may already have a trailing slash or look like "public://".
    if (substr($file_dir, -1) != '/') {
      $file_dir .= '/';
    }
    $destination = $this->fileSystem
      ->getDestinationFilename($file_dir . $filename, FileExists::Rename);

    if (!$destination) {
      $errors[] = $bad_dir;
    }
    return $errors;
  }

  /**
   * Get scheme from directory URI.
   *
   * @param string $directory
   *   Directory URI.
   *
   * @return bool|string
   *   Valid scheme or FALSE on failure.
   */
  protected function getScheme(string $directory) {
    // Do it via separate method in order to make this code working
    // for both D8 and D9 core versions.
    if (method_exists($this->streamWrapperManager, 'getScheme')) {
      return $this->streamWrapperManager->getScheme($directory);
    }
    elseif (method_exists($this->fileSystem, 'uriScheme')) {
      return $this->fileSystem->uriScheme($directory);
    }
    return FALSE;
  }

  /**
   * Validate scheme.
   *
   * @param string $scheme
   *   Scheme to be validated.
   *
   * @return bool
   *   Validation result.
   */
  protected function isValidScheme(string $scheme):bool {
    // Do it via separate method in order to make this code working
    // for both D8 and D9 core versions.
    if (method_exists($this->streamWrapperManager, 'isValidScheme')) {
      return $this->streamWrapperManager->isValidScheme($scheme);
    }
    elseif (method_exists($this->fileSystem, 'validScheme')) {
      return $this->fileSystem->validScheme($scheme);
    }
    return FALSE;
  }

}
