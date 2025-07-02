<?php

declare(strict_types=1);

namespace Drupal\imagemagick\Plugin\ImageToolkit\Operation\imagemagick;

use Drupal\Component\Utility\Color;
use Drupal\Core\ImageToolkit\Attribute\ImageToolkitOperation;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\imagemagick\PackageSuite;

/**
 * Defines imagemagick CreateNew operation.
 */
#[ImageToolkitOperation(
  id: "imagemagick_create_new",
  toolkit: "imagemagick",
  operation: "create_new",
  label: new TranslatableMarkup("Set a new image"),
  description: new TranslatableMarkup("Creates a new transparent image.")
)]
class CreateNew extends ImagemagickImageToolkitOperationBase {

  /**
   * {@inheritdoc}
   */
  protected function arguments(): array {
    return [
      'width' => [
        'description' => 'The width of the image, in pixels',
      ],
      'height' => [
        'description' => 'The height of the image, in pixels',
      ],
      'extension' => [
        'description' => 'The extension of the image file (e.g. png, gif, etc.)',
        'required' => FALSE,
        'default' => 'png',
      ],
      'transparent_color' => [
        'description' => 'The RGB hex color for GIF transparency',
        'required' => FALSE,
        'default' => '#ffffff',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function validateArguments(array $arguments): array {
    // Assure extension is supported.
    if (!in_array($arguments['extension'], $this->getToolkit()->getSupportedExtensions())) {
      throw new \InvalidArgumentException("Invalid extension ('{$arguments['extension']}') specified for the image 'create_new' operation");
    }

    // Assure integers for width and height.
    $arguments['width'] = (int) round($arguments['width']);
    $arguments['height'] = (int) round($arguments['height']);

    // Fail when width or height are 0 or negative.
    if ($arguments['width'] <= 0) {
      throw new \InvalidArgumentException("Invalid width ('{$arguments['width']}') specified for the image 'create_new' operation");
    }
    if ($arguments['height'] <= 0) {
      throw new \InvalidArgumentException("Invalid height ({$arguments['height']}) specified for the image 'create_new' operation");
    }

    // Assure transparent color is a valid hex string.
    if ($arguments['transparent_color'] && !Color::validateHex($arguments['transparent_color'])) {
      throw new \InvalidArgumentException("Invalid transparent color ({$arguments['transparent_color']}) specified for the image 'create_new' operation");
    }

    return $arguments;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments): bool {
    // Reset the image properties and any processing argument.
    $format = $this->getToolkit()->getExecManager()->getFormatMapper()->getFormatFromExtension($arguments['extension']) ?: '';
    $this->getToolkit()->reset($arguments['width'], $arguments['height'], $format);

    // Add the required arguments to allow Imagemagick to create an image
    // from scratch.
    $this->addArguments(['-size', $arguments['width'] . 'x' . $arguments['height']]);

    // Transparent color syntax for GIF files differs by package.
    if ($arguments['extension'] === 'gif') {
      $this->addArguments(
        match ($this->getToolkit()->getExecManager()->getPackageSuite()) {
          PackageSuite::Imagemagick => [
            'xc:transparent',
            '-transparent-color',
            $arguments['transparent_color'],
          ],
          PackageSuite::Graphicsmagick => [
            'xc:' . $arguments['transparent_color'],
            '-transparent', $arguments['transparent_color'],
          ],
        }
      );
    }
    else {
      $this->addArguments(['xc:transparent']);
    }

    return TRUE;
  }

}
