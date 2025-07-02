<?php

declare(strict_types=1);

namespace Drupal\imagemagick\Plugin\FileMetadata;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\file_mdm\FileMetadataException;
use Drupal\file_mdm\Plugin\Attribute\FileMetadata;
use Drupal\file_mdm\Plugin\FileMetadata\FileMetadataPluginBase;
use Drupal\imagemagick\ArgumentMode;
use Drupal\imagemagick\Event\ImagemagickExecutionEvent;
use Drupal\imagemagick\ImagemagickExecArguments;
use Drupal\imagemagick\ImagemagickExecManagerInterface;
use Drupal\imagemagick\PackageCommand;
use Drupal\imagemagick\PackageSuite;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * FileMetadata plugin for ImageMagick's identify results.
 */
#[FileMetadata(
  id: 'imagemagick_identify',
  title: new TranslatableMarkup('ImageMagick identify'),
  help: new TranslatableMarkup('File metadata plugin for ImageMagick identify results.')
)]
class ImagemagickIdentify extends FileMetadataPluginBase {

  /**
   * The event dispatcher.
   */
  protected readonly EventDispatcherInterface $eventDispatcher;

  /**
   * The ImageMagick execution manager service.
   */
  protected readonly ImagemagickExecManagerInterface $execManager;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->execManager = $container->get(ImagemagickExecManagerInterface::class);
    $instance->eventDispatcher = $container->get(EventDispatcherInterface::class);
    return $instance;
  }

  /**
   * Returns a list of metadata keys supported by the plugin.
   *
   * Supported keys are:
   *   'format' - ImageMagick's image format identifier.
   *   'width' - Image width.
   *   'height' - Image height.
   *   'colorspace' - Image colorspace.
   *   'profiles' - Image profiles.
   *   'exif_orientation' - Image EXIF orientation (only supported formats).
   *   'source_local_path' - The local file path from where the file was
   *     parsed.
   *   'frames_count' - Number of frames in the image.
   */
  public function getSupportedKeys(?array $options = NULL): array {
    return [
      'format',
      'width',
      'height',
      'colorspace',
      'profiles',
      'exif_orientation',
      'source_local_path',
      'frames_count',
    ];
  }

  protected function doGetMetadataFromFile(): mixed {
    $data = $this->identify();
    return !empty($data) ? $data : NULL;
  }

  /**
   * Validates a file metadata key.
   *
   * @param string $key
   *   A key to determine the metadata element to be returned.
   * @param string $method
   *   The caller method.
   *
   * @return bool
   *   TRUE if the key is valid.
   *
   * @throws \Drupal\file_mdm\FileMetadataException
   *   In case the key is invalid.
   */
  protected function validateKey(string $key, string $method): bool {
    if (!in_array($key, $this->getSupportedKeys(), TRUE)) {
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
      switch ($key) {
        case 'source_local_path':
          return $this->metadata['source_local_path'] ?? NULL;

        case 'frames_count':
          return isset($this->metadata['frames']) ? count($this->metadata['frames']) : 0;

        default:
          return $this->metadata['frames'][0][$key] ?? NULL;

      }
    }
  }

  protected function doSetMetadata(mixed $key, mixed $value): bool {
    $this->validateKey($key, __FUNCTION__);
    switch ($key) {
      case 'source_local_path':
        $this->metadata['source_local_path'] = $value;
        return TRUE;

      case 'frames_count':
        return FALSE;

      default:
        $this->metadata['frames'][0][$key] = $value;
        return TRUE;

    }
  }

  protected function doRemoveMetadata(mixed $key): bool {
    $this->validateKey($key, __FUNCTION__);
    switch ($key) {
      case 'source_local_path':
        if (isset($this->metadata['source_local_path'])) {
          unset($this->metadata['source_local_path']);
          return TRUE;
        }
        return FALSE;

      default:
        return FALSE;

    }
  }

  protected function getMetadataToCache(): mixed {
    $metadata = $this->metadata;
    // Avoid caching the source_local_path.
    unset($metadata['source_local_path']);
    return $metadata;
  }

  /**
   * Calls the identify executable on the specified file.
   *
   * @return array
   *   The array with identify metadata, if the file was parsed correctly.
   *   NULL otherwise.
   */
  protected function identify(): array {
    $arguments = new ImagemagickExecArguments($this->execManager);

    // Add source file.
    $arguments->setSource($this->getLocalTempPath());

    // Prepare the -format argument according to the graphics package in use.
    match ($this->execManager->getPackageSuite()) {
      PackageSuite::Imagemagick => $arguments->add(
        [
          '-format',
          'format:%[magick]|width:%[width]|height:%[height]|colorspace:%[colorspace]|profiles:%[profiles]|exif_orientation:%[EXIF:Orientation]\\n',
        ],
        ArgumentMode::PreSource
      ),
      PackageSuite::Graphicsmagick => $arguments->add(
        [
          '-format',
          'format:%m|width:%w|height:%h|exif_orientation:%[EXIF:Orientation]\\n',
        ],
        ArgumentMode::PreSource
      ),
    };

    // Allow modules to alter source file and the command line parameters.
    $this->eventDispatcher->dispatch(new ImagemagickExecutionEvent($arguments), ImagemagickExecutionEvent::ENSURE_SOURCE_LOCAL_PATH);
    $this->eventDispatcher->dispatch(new ImagemagickExecutionEvent($arguments), ImagemagickExecutionEvent::PRE_IDENTIFY_EXECUTE);

    // Execute the 'identify' command.
    $output = '';
    $error = '';
    $ret = $this->execManager->execute(PackageCommand::Identify, $arguments, $output, $error);

    // Process results.
    $data = [];
    if ($ret) {
      // Remove any CR character (GraphicsMagick on Windows produces such).
      $output = str_replace("\r", '', $output);

      // Builds the frames info.
      $frames = [];
      $frames_tmp = explode("\n", $output);
      // Remove empty items at the end of the array.
      while (empty($frames_tmp[count($frames_tmp) - 1])) {
        array_pop($frames_tmp);
      }
      foreach ($frames_tmp as $i => $frame) {
        $info = explode('|', $frame);
        foreach ($info as $item) {
          [$key, $value] = explode(':', $item);
          if (trim($key) === 'profiles') {
            $profiles_tmp = empty($value) ? [] : explode(',', $value);
            $frames[$i][trim($key)] = $profiles_tmp;
          }
          else {
            $frames[$i][trim($key)] = trim($value);
          }
        }
      }
      $data['frames'] = $frames;
      // Adds the local file path that was resolved via
      // event subscriber implementations.
      $data['source_local_path'] = $arguments->getSourceLocalPath();
    }

    return $data;
  }

}
