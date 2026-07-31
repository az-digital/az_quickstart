<?php

namespace Drupal\az_media\Twig;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides a `media_alt` Twig filter for SDCs.
 *
 * Resolves the alt text stored on the media item that a stream-wrapper URI
 * belongs to, so a component can fall back to the Media Library's own alt
 * text when no per-placement override was entered.
 *
 * An SDC image prop carries only a URI string - Canvas resolves
 * media -> file -> uri and hands the template the last of those. Recovering
 * the alt text therefore means walking that chain backwards: uri -> file ->
 * media. Deliberately generic (any image-bearing SDC can use it), which is
 * why it lives in az_media rather than in a consuming module.
 *
 * @todo Drop this if an image prop ever carries `alt` directly.
 *
 * @see \Drupal\az_media\Twig\ImageStyleTwigExtension
 */
class MediaAltTwigExtension extends AbstractExtension {

  /**
   * Per-request memo of resolved alt text, keyed by URI.
   *
   * The lookup costs two entity queries, and a page can place the same image
   * many times (a Ranking Deck of image cards, for example).
   *
   * @var array<string, string>
   */
  protected array $cache = [];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getFilters(): array {
    return [
      new TwigFilter('media_alt', [$this, 'mediaAlt']),
    ];
  }

  /**
   * Looks up the alt text of the media item owning a stream-wrapper URI.
   *
   * @param string|null $uri
   *   A stream-wrapper URI (e.g. public://foo.jpg), or NULL/empty.
   * @param string $field_name
   *   The media source image field to search. Defaults to az_media's own
   *   image field.
   *
   * @return string
   *   The media item's alt text, or an empty string when the URI is empty,
   *   no file or media item matches it, or the alt text is itself empty.
   *   An empty result is meaningful, not merely a failure: on a media item,
   *   empty alt text is how decorative_image_widget records "decorative".
   *
   * @see \Drupal\decorative_image_widget\DecorativeImageWidgetHelper
   */
  public function mediaAlt(?string $uri, string $field_name = 'field_media_az_image'): string {
    if (empty($uri)) {
      return '';
    }
    $key = $field_name . ':' . $uri;
    if (isset($this->cache[$key])) {
      return $this->cache[$key];
    }
    $this->cache[$key] = '';

    // Alt text is a nicety; never let resolving it take down a page. An
    // unknown $field_name makes the entity query throw, and a template is
    // free to pass one. Degrade to '' like the sibling image_style filter
    // degrades to an unstyled URL.
    try {
      $file_ids = $this->entityTypeManager->getStorage('file')->getQuery()
        ->accessCheck(FALSE)
        ->condition('uri', $uri)
        ->range(0, 1)
        ->execute();
      if (!$file_ids) {
        return '';
      }

      // A file can in principle be referenced by more than one media item;
      // take the lowest ID for a stable, repeatable answer rather than an
      // arbitrary one. In practice the Media Library creates one media item
      // per upload.
      $media_ids = $this->entityTypeManager->getStorage('media')->getQuery()
        ->accessCheck(FALSE)
        ->condition($field_name . '.target_id', reset($file_ids))
        ->sort('mid')
        ->range(0, 1)
        ->execute();
      if (!$media_ids) {
        return '';
      }

      $media = $this->entityTypeManager->getStorage('media')->load(reset($media_ids));
      if (!$media || !$media->hasField($field_name)) {
        return '';
      }
      $values = $media->get($field_name)->getValue();
      $this->cache[$key] = (string) ($values[0]['alt'] ?? '');
    }
    catch (\Throwable $e) {
      return '';
    }

    return $this->cache[$key];
  }

}
