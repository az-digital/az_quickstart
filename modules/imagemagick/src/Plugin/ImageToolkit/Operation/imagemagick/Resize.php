<?php

declare(strict_types=1);

namespace Drupal\imagemagick\Plugin\ImageToolkit\Operation\imagemagick;

use Drupal\Core\ImageToolkit\Attribute\ImageToolkitOperation;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines imagemagick resize operation.
 */
#[ImageToolkitOperation(
  id: "imagemagick_resize",
  toolkit: "imagemagick",
  operation: "resize",
  label: new TranslatableMarkup("Resize"),
  description: new TranslatableMarkup("Resizes an image to the given dimensions (ignoring aspect ratio).")
)]
class Resize extends ImagemagickImageToolkitOperationBase {

  /**
   * {@inheritdoc}
   */
  protected function arguments(): array {
    return [
      'width' => [
        'description' => 'The new width of the resized image, in pixels',
      ],
      'height' => [
        'description' => 'The new height of the resized image, in pixels',
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
    // Assure integers for all arguments.
    $arguments['width'] = (int) round($arguments['width']);
    $arguments['height'] = (int) round($arguments['height']);

    // Fail when width or height are 0 or negative.
    if ($arguments['width'] <= 0) {
      throw new \InvalidArgumentException("Invalid width ({$arguments['width']}) specified for the image 'resize' operation");
    }
    if ($arguments['height'] <= 0) {
      throw new \InvalidArgumentException("Invalid height ({$arguments['height']}) specified for the image 'resize' operation");
    }

    return $arguments;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments = []): bool {
    if (!empty($arguments['filter'])) {
      $this->addArguments(['-filter', $arguments['filter']]);
    }
    $this->addArguments([
      '-resize',
      $arguments['width'] . 'x' . $arguments['height'] . '!',
    ]);
    $this->getToolkit()->setWidth($arguments['width'])->setHeight($arguments['height']);
    return TRUE;
  }

}
