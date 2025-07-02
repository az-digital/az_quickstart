<?php

declare(strict_types=1);

namespace Drupal\imagemagick\Plugin\ImageToolkit\Operation\imagemagick;

use Drupal\Core\ImageToolkit\Attribute\ImageToolkitOperation;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines imagemagick Crop operation.
 */
#[ImageToolkitOperation(
  id: "imagemagick_crop",
  toolkit: "imagemagick",
  operation: "crop",
  label: new TranslatableMarkup("Crop"),
  description: new TranslatableMarkup("Crops an image to a rectangle specified by the given dimensions.")
)]
class Crop extends ImagemagickImageToolkitOperationBase {

  /**
   * {@inheritdoc}
   */
  protected function arguments(): array {
    return [
      'x' => [
        'description' => 'The starting x offset at which to start the crop, in pixels',
      ],
      'y' => [
        'description' => 'The starting y offset at which to start the crop, in pixels',
      ],
      'width' => [
        'description' => 'The width of the cropped area, in pixels',
        'required' => FALSE,
        'default' => NULL,
      ],
      'height' => [
        'description' => 'The height of the cropped area, in pixels',
        'required' => FALSE,
        'default' => NULL,
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

    // Assure at least one dimension.
    if (empty($arguments['width']) && empty($arguments['height'])) {
      throw new \InvalidArgumentException("At least one dimension ('width' or 'height') must be provided to the image 'crop' operation");
    }

    // Preserve aspect.
    $aspect = $this->getToolkit()->getHeight() / $this->getToolkit()->getWidth();
    $arguments['height'] = empty($arguments['height']) ? $arguments['width'] * $aspect : $arguments['height'];
    $arguments['width'] = empty($arguments['width']) ? $arguments['height'] / $aspect : $arguments['width'];

    // Assure integers for all arguments.
    foreach (['x', 'y', 'width', 'height'] as $key) {
      $arguments[$key] = (int) round($arguments[$key]);
    }

    // Fail when width or height are 0 or negative.
    if ($arguments['width'] <= 0) {
      throw new \InvalidArgumentException("Invalid width ('{$arguments['width']}') specified for the image 'crop' operation");
    }
    if ($arguments['height'] <= 0) {
      throw new \InvalidArgumentException("Invalid height ('{$arguments['height']}') specified for the image 'crop' operation");
    }

    return $arguments;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments): bool {
    // Even though the crop effect in Drupal core does not allow for negative
    // offsets, ImageMagick supports them. Also note: if $x and $y are set to
    // NULL then crop will create tiled images so we convert these to ints.
    $this->addArguments([
      '-crop',
      sprintf('%dx%d%+d%+d', $arguments['width'], $arguments['height'], $arguments['x'], $arguments['y']),
      '+repage',
    ]);
    $this->getToolkit()->setWidth($arguments['width'])->setHeight($arguments['height']);
    return TRUE;
  }

}
