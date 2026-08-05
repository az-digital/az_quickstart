<?php

namespace Drupal\az_media\Twig;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides a `media_alt` Twig filter for components.
 *
 * Looks up the alt text an editor typed in the Media Library, so a
 * component can use it when nobody filled in alt text for this particular
 * placement:
 *
 * @code
 * {% set alt = alt|default('') ?: src|media_alt %}
 * @endcode
 *
 * The lookup is backwards. When an editor picks an image, Canvas walks
 * media -> file -> uri and hands the template only the last one, so all we
 * have is a string like public://cactus.jpg. Getting to the alt text means
 * retracing those steps: find the file with that uri, find the media item
 * pointing at that file, then read its alt.
 *
 * Any component with an image can use this, which is why it lives in
 * az_media rather than in one consuming module.
 *
 * @todo Drop this if an image prop ever carries `alt` directly.
 *
 * @see \Drupal\az_media\Twig\ImageStyleTwigExtension
 */
class MediaAltTwigExtension extends AbstractExtension {

  /**
   * Alt text already looked up on this request, keyed by field name and URI.
   *
   * Each lookup costs two entity queries and a load, and one page can place
   * the same image many times - a Ranking Deck of image cards, for example.
   * Empty results get stored too, so a URI with no media item behind it is
   * only looked up once.
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

    // Wrap the whole lookup so a failure here can never take down a page.
    // For example, a template is free to pass a $field_name that no media
    // type has, and the entity query throws on it. Alt text is worth
    // having, not worth a white screen - so fall back to '', the same way
    // the sibling image_style filter falls back to an unstyled URL.
    try {
      $file_ids = $this->entityTypeManager->getStorage('file')->getQuery()
        ->accessCheck(FALSE)
        ->condition('uri', $uri)
        ->range(0, 1)
        ->execute();
      if (!$file_ids) {
        return '';
      }

      // Sort by mid so repeated lookups return the same media item. Two
      // media items can point at one file if someone uploaded the same
      // image twice, and without the sort we would get whichever the
      // database handed back first. The Media Library makes one media
      // item per upload, so this is rare.
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
