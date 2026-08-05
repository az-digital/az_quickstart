<?php

namespace Drupal\az_media\Twig;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\ImageToolkit\ImageToolkitManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides an `image_style` Twig filter for components.
 *
 * A component template gets its image as a URI string like
 * public://cactus.jpg. Image styles (resize, crop, convert to WebP) are
 * normally applied through a render array, which a component prop can't
 * hold. This filter bridges the gap:
 *
 * @code
 * <img src="{{ src|image_style('az_ranking_responsive') }}">
 * @endcode
 *
 * It hands back the URL of the styled copy. Drupal generates that copy the
 * first time someone requests it.
 *
 * Three cases fall back to the plain file URL instead, so a page never
 * breaks over a styling problem: an empty URI, a style name that doesn't
 * exist, and a file the image toolkit can't read.
 */
class ImageStyleTwigExtension extends AbstractExtension {

  public function __construct(
    protected FileUrlGeneratorInterface $fileUrlGenerator,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ImageToolkitManager $imageToolkitManager,
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
   *   The styled derivative's URL; a plain file_url()-equivalent URL if the
   *   named style doesn't exist or the file's format cannot be processed by
   *   the image toolkit; or an empty string if $uri is empty.
   */
  public function applyImageStyle(?string $uri, string $style_name): string {
    if (empty($uri)) {
      return '';
    }
    // If the toolkit can't read this format, hand back the plain URL.
    // For example an SVG: GD only handles png, jpeg, jpg, jpe, gif, webp
    // and avif, so public://logo.svg would turn into a logo.svg.webp
    // derivative URL that can never be generated and 404s. Serving the SVG
    // unstyled is the right answer anyway - a vector scales on its own.
    if (!$this->toolkitSupports($uri)) {
      return $this->fileUrlGenerator->generateString($uri);
    }
    $style = $this->entityTypeManager->getStorage('image_style')->load($style_name);
    if ($style) {
      return $style->buildUrl($uri);
    }
    return $this->fileUrlGenerator->generateString($uri);
  }

  /**
   * Whether the active image toolkit can process this file's format.
   *
   * Read from the toolkit rather than hard-coded, so the answer stays correct
   * on a site running ImageMagick instead of GD.
   *
   * @param string $uri
   *   The file URI to test.
   *
   * @return bool
   *   TRUE when the toolkit lists the file's extension as supported.
   */
  protected function toolkitSupports(string $uri): bool {
    // The `?? $uri` matters: parse_url() treats public://foo.svg as scheme
    // plus host with no path, so it returns NULL and there is no extension
    // to find. Only a URI with a subdirectory - public://dir/foo.svg -
    // gives it a path. Falling back to the whole URI covers the flat case.
    $extension = strtolower(pathinfo(parse_url($uri, PHP_URL_PATH) ?? $uri, PATHINFO_EXTENSION));
    if ($extension === '') {
      return FALSE;
    }
    $toolkit = $this->imageToolkitManager->getDefaultToolkit();
    return in_array($extension, $toolkit::getSupportedExtensions(), TRUE);
  }

}
