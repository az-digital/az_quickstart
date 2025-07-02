<?php

declare(strict_types=1);

namespace Drupal\imagemagick\Plugin\ImageToolkit\Operation\imagemagick;

use Drupal\Core\ImageToolkit\ImageToolkitOperationBase;
use Drupal\imagemagick\ArgumentMode;
use Drupal\imagemagick\ImagemagickExecArguments;
use Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit;

/**
 * Base image toolkit operation class for Imagemagick.
 */
abstract class ImagemagickImageToolkitOperationBase extends ImageToolkitOperationBase {

  /**
   * The correctly typed image toolkit for imagemagick operations.
   *
   * @return \Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit
   *   The correctly typed image toolkit for imagemagick operations.
   */
  protected function getToolkit(): ImagemagickToolkit {
    /** @var \Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit $toolkit */
    $toolkit = parent::getToolkit();
    return $toolkit;
  }

  /**
   * Helper to add command line arguments.
   *
   * Adds the originating operation and plugin id to the $info array.
   *
   * @param string[] $arguments
   *   The command line arguments to be added.
   * @param \Drupal\imagemagick\ArgumentMode $mode
   *   (optional) The mode of the argument in the command line. Determines if
   *   the argument should be placed before or after the source image file path.
   *   Defaults to ArgumentMode::PostSource.
   * @param int $index
   *   (optional) The position of the argument in the arguments array.
   *   Reflects the sequence of arguments in the command line. Defaults to
   *   ImagemagickExecArguments::APPEND.
   * @param array $info
   *   (optional) An optional array with information about the argument.
   *   Defaults to an empty array.
   *
   * @return \Drupal\imagemagick\ImagemagickExecArguments
   *   The Imagemagick arguments.
   */
  protected function addArguments(array $arguments, ArgumentMode $mode = ArgumentMode::PostSource, int $index = ImagemagickExecArguments::APPEND, array $info = []): ImagemagickExecArguments {
    $plugin_definition = $this->getPluginDefinition();
    $info = array_merge($info, [
      'image_toolkit_operation' => $plugin_definition['operation'],
      'image_toolkit_operation_plugin_id' => $plugin_definition['id'],
    ]);
    return $this->getToolkit()->arguments()->add($arguments, $mode, $index, $info);
  }

}
