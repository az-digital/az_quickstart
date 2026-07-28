<?php

namespace Drupal\az_media\Twig;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
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
    protected StreamWrapperManagerInterface $streamWrapperManager,
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
   * Also accepts a plain absolute URL, since some SDC props (whose
   * x-allowed-schemes includes http/https, e.g. to accept a plain-URL
   * example value) resolve to an absolute URL rather than a stream-wrapper
   * URI even for a file that's actually stored locally on this site -
   * ImageStyle::buildUri() only understands real Drupal stream-wrapper
   * schemes, and silently mangles anything else (including an absolute
   * URL pointing back at this site's own public files) into a bogus local
   * derivative path instead of rejecting it. If the given value turns out
   * to be a genuinely external URL, no image style processing is possible
   * for it at all (there's nothing local to generate a derivative from),
   * so it's returned unchanged rather than passed to buildUrl().
   *
   * @param string|null $uri
   *   A stream-wrapper URI (e.g. public://foo.jpg), an absolute URL
   *   pointing at this site's own public files, an arbitrary external
   *   absolute URL, or NULL/empty.
   * @param string $style_name
   *   The image style's machine name.
   *
   * @return string
   *   The styled derivative's URL; a plain file_url()-equivalent URL if
   *   the named style doesn't exist; the input unchanged if it's a
   *   non-Drupal-manageable URL (e.g. a genuinely external image); or an
   *   empty string if $uri is empty.
   */
  public function applyImageStyle(?string $uri, string $style_name): string {
    if (empty($uri)) {
      return '';
    }

    $uri = $this->resolveToStreamWrapperUri($uri);

    $scheme = StreamWrapperManager::getScheme($uri);
    if ($scheme !== FALSE && !$this->streamWrapperManager->isValidScheme($scheme)) {
      return $uri;
    }

    $style = $this->entityTypeManager->getStorage('image_style')->load($style_name);
    if ($style) {
      return $style->buildUrl($uri);
    }
    return $this->fileUrlGenerator->generateString($uri);
  }

  /**
   * Converts a local public-files URL back into a public:// URI.
   *
   * Leaves anything else (a real stream-wrapper URI already, or a
   * genuinely external URL) unchanged.
   */
  protected function resolveToStreamWrapperUri(string $uri): string {
    if (StreamWrapperManager::getScheme($uri) === FALSE) {
      return $uri;
    }
    $public_base_url = $this->streamWrapperManager->getViaScheme('public')->getExternalUrl();
    return str_starts_with($uri, $public_base_url)
      ? 'public://' . substr($uri, strlen($public_base_url))
      : $uri;
  }

}
