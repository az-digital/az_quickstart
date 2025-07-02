<?php

declare(strict_types=1);

namespace Drupal\imagemagick\Event;

use Drupal\imagemagick\ImagemagickExecArguments;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Defines the ImagemagickExecutionEvent.
 */
class ImagemagickExecutionEvent extends Event {

  /**
   * Fires when the toolkit is ensuring a local file path for the source image.
   *
   * @Event
   *
   * @see \Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit::ensureSourceLocalPath()
   *
   * @var string
   */
  const ENSURE_SOURCE_LOCAL_PATH = 'imagemagick.toolkit.ensureSourceLocalPath';

  /**
   * Fires after an image has been saved by the ImageMagick toolkit.
   *
   * @Event
   *
   * @see \Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit::save()
   *
   * @var string
   */
  const POST_SAVE = 'imagemagick.toolkit.postSave';

  /**
   * Fires before the 'convert' command is executed.
   *
   * @Event
   *
   * @see \Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit::convert()
   *
   * @var string
   */
  const PRE_CONVERT_EXECUTE = 'imagemagick.convert.preExecute';

  /**
   * Fires before the 'identify' command is executed.
   *
   * @Event
   *
   * @see \Drupal\imagemagick\Plugin\FileMetadata\ImagemagickIdentify::identify()
   *
   * @var string
   */
  const PRE_IDENTIFY_EXECUTE = 'imagemagick.identify.preExecute';

  /**
   * Constructs the object.
   *
   * @param \Drupal\imagemagick\ImagemagickExecArguments $arguments
   *   The ImageMagick/GraphicsMagick execution arguments object.
   */
  public function __construct(
    protected readonly ImagemagickExecArguments $arguments,
  ) {
  }

  /**
   * Returns the ImagemagickExecArguments object.
   *
   * @return \Drupal\imagemagick\ImagemagickExecArguments
   *   The ImageMagick/GraphicsMagick execution arguments object.
   */
  public function getExecArguments(): ImagemagickExecArguments {
    return $this->arguments;
  }

}
