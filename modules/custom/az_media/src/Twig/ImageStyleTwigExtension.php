<?php

namespace Drupal\az_media\Twig;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides an `image_style` Twig filter for SDCs.
 *
 * Lets a component template apply a named Drupal image style to a
 * stream-wrapper URI without going through a themed render array
 * (`#theme => image_style` / `image_formatter`), which SDC props can't
 * carry. Falls back to plain file_url() behavior if the style doesn't
 * exist or the URI is empty, so a missing/misconfigured style degrades
 * gracefully instead of breaking the page.
 */
class ImageStyleTwigExtension extends AbstractExtension {

  public function __construct(
    protected FileUrlGeneratorInterface $fileUrlGenerator,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getFilters(): array {
    return [
      new TwigFilter('image_style', [$this, 'applyImageStyle']),
    ];
  }

  /**
   * Applies a named image style to a stream-wrapper URI.
   *
   * @param string|null $uri
   *   A stream-wrapper URI (e.g. public://foo.jpg), or NULL/empty.
   * @param string $style_name
   *   The image style's machine name.
   *
   * @return string
   *   The styled derivative's URL, or a plain file_url()-equivalent URL if
   *   the named style doesn't exist, or an empty string if $uri is empty.
   */
  public function applyImageStyle(?string $uri, string $style_name): string {
    if (empty($uri)) {
      return '';
    }
    $style = $this->entityTypeManager->getStorage('image_style')->load($style_name);
    if ($style) {
      return $style->buildUrl($uri);
    }
    return $this->fileUrlGenerator->generateString($uri);
  }

}
