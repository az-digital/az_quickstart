<?php

declare(strict_types=1);

namespace Drupal\imagemagick\Plugin\ImageToolkit\Operation\imagemagick;

use Drupal\Core\ImageToolkit\Attribute\ImageToolkitOperation;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines imagemagick Scale and crop operation.
 */
#[ImageToolkitOperation(
  id: "imagemagick_scale_and_crop",
  toolkit: "imagemagick",
  operation: "scale_and_crop",
  label: new TranslatableMarkup("Scale and crop"),
  description: new TranslatableMarkup("Scales an image to the exact width and height given. This plugin achieves the target aspect ratio by cropping the original image equally on both sides, or equally on the top and bottom. This function is useful to create uniform sized avatars from larger images.")
)]
class ScaleAndCrop extends ImagemagickImageToolkitOperationBase {

  /**
   * {@inheritdoc}
   */
  protected function arguments(): array {
    return [
      'x' => [
        'description' => 'The horizontal offset for the start of the crop, in pixels',
        'required' => FALSE,
        'default' => NULL,
      ],
      'y' => [
        'description' => 'The vertical offset for the start the crop, in pixels',
        'required' => FALSE,
        'default' => NULL,
      ],
      'width' => [
        'description' => 'The target width, in pixels',
      ],
      'height' => [
        'description' => 'The target height, in pixels',
      ],
      'filter' => [
        'description' => 'An optional filter to apply for the resize',
        'required' => FALSE,
        'default' => '',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function validateArguments(array $arguments): array {
    // Fail if no dimensions available for current image.
    if (is_null($this->getToolkit()->getWidth()) || is_null($this->getToolkit()->getHeight())) {
      throw new \RuntimeException("No image dimensions available for the image '{$this->getPluginDefinition()['operation']}' operation");
    }

    $actualWidth = $this->getToolkit()->getWidth();
    $actualHeight = $this->getToolkit()->getHeight();

    $scaleFactor = max($arguments['width'] / $actualWidth, $arguments['height'] / $actualHeight);

    $arguments['x'] = isset($arguments['x']) ?
      (int) round($arguments['x']) :
      (int) round(($actualWidth * $scaleFactor - $arguments['width']) / 2);
    $arguments['y'] = isset($arguments['y']) ?
      (int) round($arguments['y']) :
      (int) round(($actualHeight * $scaleFactor - $arguments['height']) / 2);
    $arguments['resize'] = [
      'width' => (int) round($actualWidth * $scaleFactor),
      'height' => (int) round($actualHeight * $scaleFactor),
      'filter' => $arguments['filter'],
    ];

    // Fail when width or height are 0 or negative.
    if ($arguments['width'] <= 0) {
      throw new \InvalidArgumentException("Invalid width ('{$arguments['width']}') specified for the image 'scale_and_crop' operation");
    }
    if ($arguments['height'] <= 0) {
      throw new \InvalidArgumentException("Invalid height ('{$arguments['height']}') specified for the image 'scale_and_crop' operation");
    }

    return $arguments;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments = []): bool {
    return $this->getToolkit()->apply('resize', $arguments['resize'])
        && $this->getToolkit()->apply('crop', $arguments);
  }

}
