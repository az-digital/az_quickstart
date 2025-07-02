<?php

declare(strict_types=1);

namespace Drupal\imagemagick\Plugin\ImageToolkit\Operation\imagemagick;

use Drupal\Core\ImageToolkit\Attribute\ImageToolkitOperation;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines imagemagick Scale operation.
 */
#[ImageToolkitOperation(
  id: "imagemagick_scale",
  toolkit: "imagemagick",
  operation: "scale",
  label: new TranslatableMarkup("Scale"),
  description: new TranslatableMarkup("Scales an image while maintaining aspect ratio. The resulting image can be smaller for one or both target dimensions.")
)]
class Scale extends Resize {

  /**
   * {@inheritdoc}
   */
  protected function arguments(): array {
    return [
      'width' => [
        'description' => 'The target width, in pixels. This value is omitted then the scaling will based only on the height value',
        'required' => FALSE,
        'default' => NULL,
      ],
      'height' => [
        'description' => 'The target height, in pixels. This value is omitted then the scaling will based only on the width value',
        'required' => FALSE,
        'default' => NULL,
      ],
      'upscale' => [
        'description' => 'Boolean indicating that files smaller than the dimensions will be scaled up. This generally results in a low quality image',
        'required' => FALSE,
        'default' => FALSE,
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

    // Assure at least one dimension.
    if (empty($arguments['width']) && empty($arguments['height'])) {
      throw new \InvalidArgumentException("At least one dimension ('width' or 'height') must be provided to the image 'scale' operation");
    }

    // Cast width and height as integers.
    if (isset($arguments['width'])) {
      $arguments['width'] = (int) $arguments['width'];
    }
    if (isset($arguments['height'])) {
      $arguments['height'] = (int) $arguments['height'];
    }

    // Calculate one of the dimensions from the other target dimension,
    // ensuring the same aspect ratio as the source dimensions. If one of the
    // target dimensions is missing, that is the one that is calculated. If both
    // are specified then the dimension calculated is the one that would not be
    // calculated to be bigger than its target.
    $aspect = $this->getToolkit()->getHeight() / $this->getToolkit()->getWidth();
    if (($arguments['width'] && !$arguments['height']) || ($arguments['width'] && $arguments['height'] && $aspect < $arguments['height'] / $arguments['width'])) {
      $arguments['height'] = (int) round($arguments['width'] * $aspect);
    }
    else {
      $arguments['width'] = (int) round($arguments['height'] / $aspect);
    }

    // Assure integers for all arguments.
    $arguments['width'] = (int) round($arguments['width']);
    $arguments['height'] = (int) round($arguments['height']);

    // Fail when width or height are 0 or negative.
    if ($arguments['width'] <= 0) {
      throw new \InvalidArgumentException("Invalid width ('{$arguments['width']}') specified for the image 'scale' operation");
    }
    if ($arguments['height'] <= 0) {
      throw new \InvalidArgumentException("Invalid height ('{$arguments['height']}') specified for the image 'scale' operation");
    }

    return $arguments;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments = []): bool {
    // Don't scale if we don't change the dimensions at all.
    if ($arguments['width'] !== $this->getToolkit()->getWidth() || $arguments['height'] !== $this->getToolkit()->getHeight()) {
      // Don't upscale if the option isn't enabled.
      if ($arguments['upscale'] || ($arguments['width'] <= $this->getToolkit()->getWidth() && $arguments['height'] <= $this->getToolkit()->getHeight())) {
        return parent::execute($arguments);
      }
    }
    return TRUE;
  }

}
