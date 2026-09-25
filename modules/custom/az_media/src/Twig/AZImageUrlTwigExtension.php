<?php

namespace Drupal\az_media\Twig;

use Drupal\az_media\AZImageUrlGenerator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides the `az_media_image_style` Twig filter for components.
 *
 * Any template can call it on an image URL or stream-wrapper URI, naming
 * the image style to apply:
 *
 * @code
 * <img src="{{ image.src|az_media_image_style('max_1300x1300') }}">
 * @endcode
 *
 * @see \Drupal\az_media\AZImageUrlGenerator
 */
class AZImageUrlTwigExtension extends AbstractExtension {

  public function __construct(
    protected AZImageUrlGenerator $generator,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getFilters(): array {
    return [
      new TwigFilter('az_media_image_style', [$this->generator, 'getImageStyleUrl']),
    ];
  }

}
