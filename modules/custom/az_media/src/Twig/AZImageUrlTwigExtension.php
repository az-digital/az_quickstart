<?php

namespace Drupal\az_media\Twig;

use Drupal\az_media\AZImageUrlGenerator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides the `az_media_image_style` Twig filter for components.
 *
 * Not specific to any one component - any template can call it on an image
 * prop, whichever render path built it:
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
