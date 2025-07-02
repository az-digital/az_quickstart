<?php

declare(strict_types=1);

namespace Drupal\imagemagick\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\file_mdm\FileMetadataManagerInterface;
use Drupal\imagemagick\ArgumentMode;
use Drupal\imagemagick\Event\ImagemagickExecutionEvent;
use Drupal\imagemagick\ImagemagickExecArguments;
use Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Imagemagick's module Event Subscriber.
 */
class ImagemagickEventSubscriber implements EventSubscriberInterface {

  /**
   * The module configuration settings.
   */
  protected ImmutableConfig $imagemagickSettings;

  /**
   * Constructs an ImagemagickEventSubscriber object.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $streamWrapperManager
   *   The stream wrapper manager service.
   * @param \Drupal\file_mdm\FileMetadataManagerInterface $fileMetadataManager
   *   The file metadata manager service.
   */
  public function __construct(
    #[Autowire(service: 'logger.channel.image')]
    protected readonly LoggerInterface $logger,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly FileSystemInterface $fileSystem,
    protected readonly StreamWrapperManagerInterface $streamWrapperManager,
    protected readonly FileMetadataManagerInterface $fileMetadataManager,
  ) {
    $this->imagemagickSettings = $this->configFactory->get('imagemagick.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      ImagemagickExecutionEvent::ENSURE_SOURCE_LOCAL_PATH => 'ensureSourceLocalPath',
      ImagemagickExecutionEvent::POST_SAVE => 'postSave',
      ImagemagickExecutionEvent::PRE_CONVERT_EXECUTE => 'preConvertExecute',
      ImagemagickExecutionEvent::PRE_IDENTIFY_EXECUTE => 'preIdentifyExecute',
    ];
  }

  /**
   * Ensures source image URI to a local filesystem path.
   *
   * @param \Drupal\imagemagick\ImagemagickExecArguments $arguments
   *   The ImageMagick/GraphicsMagick execution arguments object.
   */
  protected function doEnsureSourceLocalPath(ImagemagickExecArguments $arguments): void {
    // Early return if already set.
    if (!empty($arguments->getSourceLocalPath())) {
      return;
    }

    $source = $arguments->getSource();
    if (!$this->streamWrapperManager->isValidUri($source)) {
      // The value of $source is likely a file path already.
      $arguments->setSourceLocalPath($source);
    }
    else {
      // If we can resolve the realpath of the file, then the file is local
      // and we can assign the actual file path.
      $path = $this->fileSystem->realpath($source);
      if ($path) {
        $arguments->setSourceLocalPath($path);
      }
      else {
        // We are working with a remote file, copy the remote source file to a
        // temp one and set the local path to it.
        try {
          $temp_path = $this->fileSystem->tempnam('temporary://', 'imagemagick_');
          $this->fileSystem->unlink($temp_path);
          $temp_path .= '.' . pathinfo($source, PATHINFO_EXTENSION);
          $path = $this->fileSystem->copy($arguments->getSource(), $temp_path, FileExists::Error);
          $arguments->setSourceLocalPath($this->fileSystem->realpath($path));
          drupal_register_shutdown_function(
            [static::class, 'removeTemporaryRemoteCopy'],
            $arguments->getSourceLocalPath()
          );
        }
        catch (FileException $e) {
          $this->logger->error($e->getMessage());
        }
      }
    }
  }

  /**
   * Ensures destination image URI to a local filesystem path.
   *
   * @param \Drupal\imagemagick\ImagemagickExecArguments $arguments
   *   The ImageMagick/GraphicsMagick execution arguments object.
   */
  protected function doEnsureDestinationLocalPath(ImagemagickExecArguments $arguments): void {
    $local_path = $arguments->getDestinationLocalPath();

    // Early return if already set.
    if (!empty($local_path)) {
      return;
    }

    $destination = $arguments->getDestination();
    if (!$this->streamWrapperManager->isValidUri($destination)) {
      // The value of $destination is likely a file path already.
      $arguments->setDestinationLocalPath($destination);
    }
    else {
      // If we can resolve the realpath of the file, then the file is local
      // and we can assign its real path.
      $path = $this->fileSystem->realpath($destination);
      if ($path) {
        $arguments->setDestinationLocalPath($path);
      }
      else {
        // We are working with a remote file, set the local destination to
        // a temp local file.
        $temp_path = $this->fileSystem->tempnam('temporary://', 'imagemagick_');
        $this->fileSystem->unlink($temp_path);
        $temp_path .= '.' . pathinfo($destination, PATHINFO_EXTENSION);
        $arguments->setDestinationLocalPath($this->fileSystem->realpath($temp_path));
        drupal_register_shutdown_function(
          [static::class, 'removeTemporaryRemoteCopy'],
          $arguments->getDestinationLocalPath()
        );
      }
    }
  }

  /**
   * Adds configured arguments at the beginning of the list.
   *
   * @param \Drupal\imagemagick\ImagemagickExecArguments $arguments
   *   The ImageMagick/GraphicsMagick execution arguments object.
   */
  protected function prependArguments(ImagemagickExecArguments $arguments): void {
    // Add prepended arguments if needed.
    if ($prepend = $this->imagemagickSettings->get('prepend')) {
      // Split the prepend string in multiple space-separated tokens. Quotes,
      // both " and ', can delimit tokens with spaces inside. Such tokens can
      // contain escaped quotes too.
      // @see https://stackoverflow.com/questions/366202/regex-for-splitting-a-string-using-space-when-not-surrounded-by-single-or-double
      // @see https://stackoverflow.com/questions/6525556/regular-expression-to-match-escaped-characters-quotes
      $re = '/[^\s"\']+|"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\'/m';
      preg_match_all($re, $prepend, $tokens, PREG_SET_ORDER);
      $args = [];
      foreach ($tokens as $token) {
        // The escape character needs to be removed, Symfony Process will
        // escape the quote character again.
        $args[] = str_replace("\\", "", end($token));
      }
      $arguments->add($args, ArgumentMode::PreSource, 0);
    }
  }

  /**
   * Reacts to an image being parsed.
   *
   * Alters the settings before an image is parsed by the ImageMagick toolkit.
   *
   * ImageMagick does not support stream wrappers so this method allows to
   * resolve URIs of image files to paths on the local filesystem.
   * Modules can also decide to move files from remote systems to the local
   * file system to allow processing.
   *
   * @param \Drupal\imagemagick\Event\ImagemagickExecutionEvent $event
   *   Imagemagick execution event.
   *
   * @see \Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit::parseFile()
   * @see \Drupal\imagemagick\ImagemagickExecArguments::getSource()
   * @see \Drupal\imagemagick\ImagemagickExecArguments::setSourceLocalPath()
   * @see \Drupal\imagemagick\ImagemagickExecArguments::getSourceLocalPath()
   */
  public function ensureSourceLocalPath(ImagemagickExecutionEvent $event): void {
    $arguments = $event->getExecArguments();
    $this->doEnsureSourceLocalPath($arguments);
  }

  /**
   * Reacts to an image save.
   *
   * Alters an image after it has been converted by the ImageMagick toolkit.
   *
   * ImageMagick does not support remote file systems, so modules can decide
   * to move temporary files from the local file system to remote destination
   * systems.
   *
   * @param \Drupal\imagemagick\Event\ImagemagickExecutionEvent $event
   *   Imagemagick execution event.
   *
   * @see \Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit::save()
   * @see \Drupal\imagemagick\ImagemagickExecArguments::getDestination()
   * @see \Drupal\imagemagick\ImagemagickExecArguments::getDestinationLocalPath()
   */
  public function postSave(ImagemagickExecutionEvent $event): void {
    $arguments = $event->getExecArguments();
    $destination = $arguments->getDestination();
    if (!$this->fileSystem->realpath($destination)) {
      // We are working with a remote file, so move the temp file to the final
      // destination, replacing any existing file with the same name.
      try {
        $this->fileSystem->move($arguments->getDestinationLocalPath(), $arguments->getDestination(), FileExists::Replace);
      }
      catch (FileException $e) {
        $this->logger->error($e->getMessage());
      }
    }
  }

  /**
   * Fires before the 'identify' command is executed.
   *
   * It allows to change file paths for source and destination image files,
   * and/or to alter the command line arguments that are passed to the binaries.
   * The toolkit provides methods to prepend, add, find, get and reset
   * arguments that have already been set by image effects.
   *
   * In addition to arguments that are passed to the binaries command line for
   * execution, it is possible to push arguments to be used only by the toolkit
   * or the event subscribers. You can add/get/find such arguments by specifying
   * ImagemagickExecArguments::INTERNAL as the argument $mode in the methods.
   *
   * @param \Drupal\imagemagick\Event\ImagemagickExecutionEvent $event
   *   Imagemagick execution event.
   *
   * @see http://www.imagemagick.org/script/command-line-processing.php#output
   *
   * @see \Drupal\imagemagick\ImagemagickExecArguments
   * @see \Drupal\imagemagick\Plugin\FileMetadata\ImagemagickIdentify::identify()
   */
  public function preIdentifyExecute(ImagemagickExecutionEvent $event): void {
    $arguments = $event->getExecArguments();
    $this->prependArguments($arguments);
  }

  /**
   * Fires before the 'convert' command is executed.
   *
   * It allows to change file paths for source and destination image files,
   * and/or to alter the command line arguments that are passed to the binaries.
   * The toolkit provides methods to prepend, add, find, get and reset
   * arguments that have already been set by image effects.
   *
   * In addition to arguments that are passed to the binaries command line for
   * execution, it is possible to push arguments to be used only by the toolkit
   * or the event subscribers. You can add/get/find such arguments by specifying
   * ImagemagickExecArguments::INTERNAL as the argument $mode in the methods.
   *
   * ImageMagick automatically converts the target image to the format denoted
   * by the file extension. However, since changing the file extension is not
   * always an option, you can specify an alternative image format via
   * $arguments->setDestinationFormat('format'), where 'format' is a string
   * denoting an Imagemagick supported format, or via
   * $arguments->setDestinationFormatFromExtension('extension'), where
   * 'extension' is a string denoting an image file extension.
   *
   * When the destination format is set, it is passed to ImageMagick's convert
   * binary with the syntax "[format]:[destination]".
   *
   * @param \Drupal\imagemagick\Event\ImagemagickExecutionEvent $event
   *   Imagemagick execution event.
   *
   * @see http://www.imagemagick.org/script/command-line-processing.php#output
   * @see http://www.imagemagick.org/Usage/files/#save
   *
   * @see \Drupal\imagemagick\ImagemagickExecArguments
   * @see \Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit::convert()
   */
  public function preConvertExecute(ImagemagickExecutionEvent $event): void {
    $arguments = $event->getExecArguments();
    $this->prependArguments($arguments);
    $this->doEnsureDestinationLocalPath($arguments);

    // Coalesce Animated GIFs, if required.
    if (empty($arguments->find('/^\-coalesce/')) && (bool) $this->imagemagickSettings->get('advanced.coalesce') && in_array($arguments->getSourceFormat(), [
      'GIF',
      'GIF87',
    ])) {
      $file_md = $this->fileMetadataManager->uri($arguments->getSource());
      if ($file_md && $file_md->getMetadata(ImagemagickToolkit::FILE_METADATA_PLUGIN_ID, 'frames_count') > 1) {
        $arguments->add(["-coalesce"], ArgumentMode::PostSource, 0);
      }
    }

    // Change output image resolution to 72 ppi, if specified in settings.
    if (empty($arguments->find('/^\-density/')) && $density = (string) $this->imagemagickSettings->get('advanced.density')) {
      $arguments->add(["-density", $density, "-units", "PixelsPerInch"]);
    }

    // Apply color profile.
    if ($profile = $this->imagemagickSettings->get('advanced.profile')) {
      if (file_exists($profile)) {
        $arguments->add(['-profile', $profile]);
      }
    }
    // Or alternatively apply colorspace.
    elseif ($colorspace = $this->imagemagickSettings->get('advanced.colorspace')) {
      // Do not hi-jack settings made by effects.
      if (empty($arguments->find('/^\-colorspace/'))) {
        $arguments->add(['-colorspace', $colorspace]);
      }
    }

    // Change image quality.
    if (empty($arguments->find('/^\-quality/'))) {
      $arguments->add(['-quality', (string) $this->imagemagickSettings->get('quality')]);
    }
  }

  /**
   * Removes a temporary file created during operations on a remote file.
   *
   * Used with drupal_register_shutdown_function().
   *
   * @param string $path
   *   The temporary file realpath.
   */
  public static function removeTemporaryRemoteCopy(string $path): void {
    if (file_exists($path)) {
      \Drupal::service('file_system')->delete($path);
    }
  }

}
