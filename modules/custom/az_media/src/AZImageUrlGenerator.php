<?php

namespace Drupal\az_media;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\ImageToolkit\ImageToolkitManager;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Applies an image style to a component's image prop.
 *
 * Backs the `az_media_image_style` Twig filter, which any component template
 * can use - not just ranking-image's:
 *
 * @code
 * <img src="{{ image.src|az_media_image_style('max_1300x1300') }}">
 * @endcode
 *
 * A component prop holds a plain string, which may be a resolved URL like
 * /sites/default/files/cactus.jpg or a stream-wrapper URI like
 * public://cactus.jpg. ImageStyle::buildUrl() only takes the URI form, so
 * toStreamWrapperUri() converts a URL back into one first. Taking both
 * means a template never has to know which form its prop arrived in -
 * ranking-image, for one, is handed a URL by AZRankingComponentBuilder on
 * the paragraph path and a different URL by Canvas's own field mapping.
 *
 * Core does the rest. ImageStyleDownloadController generates the styled
 * copy the first time someone requests it, then serves it from disk, and
 * flushes it when the style changes or the source file is replaced.
 */
class AZImageUrlGenerator {

  public function __construct(
    protected StreamWrapperManagerInterface $streamWrapperManager,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
    protected ImageToolkitManager $imageToolkitManager,
    protected RequestStack $requestStack,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Builds the URL of $src styled with an image style.
   *
   * Falls back to a plain, unstyled URL rather than breaking the page when
   * styling can't apply: $src isn't a local public file, the named style
   * doesn't exist, or the image toolkit can't read the file's format (an
   * SVG, say - it scales on its own, and a styled derivative of one would
   * only 404).
   *
   * @param string|null $src
   *   A stream-wrapper URI, or a resolved URL pointing at a local public
   *   file (its query string, if any, is ignored).
   * @param string $style_name
   *   The image style's machine name.
   *
   * @return string
   *   A URL. Empty only if $src was.
   */
  public function getImageStyleUrl(?string $src, string $style_name): string {
    if (empty($src)) {
      return '';
    }

    $uri = $this->toStreamWrapperUri($src);
    if ($uri === NULL) {
      // If the file isn't a local public one - a remote URL, say - there is
      // nothing to style, so hand it back as it came.
      return $src;
    }

    if (!$this->toolkitSupports($uri)) {
      return $this->fileUrlGenerator->generateString($uri);
    }

    $style = $this->entityTypeManager->getStorage('image_style')->load($style_name);
    if (!$style) {
      return $this->fileUrlGenerator->generateString($uri);
    }

    return $this->fileUrlGenerator->transformRelative($style->buildUrl($uri));
  }

  /**
   * Resolves $src to a public:// URI, or NULL if that's not possible.
   *
   * Reverses what PublicStream::getLocalPath() does, so a URL like
   * /sites/default/files/cactus.jpg comes back as public://cactus.jpg. A
   * URI is returned as it came. Copes with a site installed in a
   * subdirectory and with an absolute URL carrying a port, and decodes the
   * path so Drupal doesn't encode it twice when it builds the styled URL.
   */
  protected function toStreamWrapperUri(string $src): ?string {
    // Drop a query string - e.g. Canvas's own ?alternateWidths= - before
    // testing or reversing it.
    $path_only = strtok($src, '?');
    if ($path_only === FALSE) {
      $path_only = $src;
    }

    if ($this->streamWrapperManager->isValidUri($path_only)) {
      return $this->streamWrapperManager->getScheme($path_only) === 'public' ? $path_only : NULL;
    }

    $public_base_path = PublicStream::basePath();
    $path_segment = parse_url($path_only, PHP_URL_PATH);
    $path = ltrim(is_string($path_segment) ? $path_segment : $path_only, '/');
    $request_base_path = trim($this->requestStack->getCurrentRequest()?->getBasePath() ?? '', '/');
    $prefix = $public_base_path . '/';

    if (str_starts_with($path, $prefix)) {
      $target = substr($path, strlen($prefix));
    }
    elseif ($request_base_path !== '' && str_starts_with($path, $request_base_path . '/' . $prefix)) {
      $target = substr($path, strlen($request_base_path . '/' . $prefix));
    }
    else {
      return NULL;
    }

    return 'public://' . rawurldecode($target);
  }

  /**
   * Whether the active image toolkit can process this file's format.
   *
   * Read from the toolkit rather than hard-coded, so the answer stays
   * correct on a site running ImageMagick instead of GD.
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
