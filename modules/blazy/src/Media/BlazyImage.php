<?php

namespace Drupal\blazy\Media;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\blazy\BlazyDefault;
use Drupal\blazy\Utility\Sanitize;
use Drupal\blazy\internals\Internals;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\media\MediaInterface;

/**
 * Provides image-related methods.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module.
 *
 * @todo recap similiraties and make them plugins.
 */
class BlazyImage {

  /**
   * Checks if the image style contains crop in the effect name.
   *
   * @var array
   */
  protected static $crop;

  /**
   * Checks if image dimensions are set.
   *
   * @var array
   */
  protected static $isCropSet;

  /**
   * Prepares CSS background image.
   *
   * @todo refactor this, to get rid of settings for blazies object at/ by 3.x.
   */
  public static function background(array $settings, $style = NULL) {
    // @tbd replace src with URL before 3.x, or keep it.
    return [
      'src' => self::toUrl($settings, $style),
      'ratio' => Ratio::compute($settings),
    ];
  }

  /**
   * Sets dimensions once to reduce method calls, if image style contains crop.
   *
   * @param array $settings
   *   The settings being modified.
   * @param object $style
   *   The image style to check for crp effect.
   */
  public static function cropDimensions(array &$settings, $style): void {
    $id = $style->id();

    if ($style && !isset(static::$isCropSet[$id])) {
      // If image style contains crop, sets dimension once, and let all inherit.
      if ($crop = self::getCrop($style)) {
        $blazies = $settings['blazies'];
        $data = self::transformDimensions($crop, $blazies);

        // Informs individual images that dimensions are already set once.
        // Do not let the first broken image screw up the rest, likely
        // non-transliterated file names, SVG, missing ones, etc.
        if ($data['width']) {
          $blazies->set('image', $data, TRUE)
            ->set('is.dimensions', TRUE);
        }
      }

      static::$isCropSet[$id] = TRUE;
    }
  }

  /**
   * Provides original unstyled image dimensions based on the given image item.
   *
   * This one is original image, not styled like self:transformDimensions().
   * Sources: formatters, filters or any hard-coded unmanaged files like VEF.
   */
  public static function dimensions(array &$settings, $item, $uri, $initial = FALSE): array {
    $blazies = $settings['blazies'];
    $_width  = 'width';
    $_height = 'height';
    $fluid   = $blazies->is('fluid');
    $which   = $initial ? 'first' : 'image';
    $height  = $blazies->get($which . '.height');
    $width   = $blazies->get($which . '.width');
    $uri     = $uri ?: $blazies->get($which . '.uri');

    // Original image sizes are stored within ImageItem, or fake one.
    // @todo remove ImageItem checks at 3.x. when all moved into blazies.image.
    if ($item) {
      // The given item might also be VideoEmbedField, unless converted using
      // BlazyOEmbed::getThumbnail(). Ensures it is not screwing up.
      if (!isset($item->width)) {
        $item = $blazies->get('image.item');
      }

      $width = $item->width ?? $width;
      $height = $item->height ?? $height;

      // Ensures the correct image item is set here on.
      $blazies->set('image.item', $item);
    }

    // Only applies when no file API, no $item, with unmanaged VEF/ WYSIWG/
    // filter image, and when image_style even failed.
    if ($uri && (!$height || !$width)) {
      $abs = $blazies->get('image.uri_root', $uri);
      $abs = BlazyFile::toAccessibleUri($abs);

      if (BlazyFile::isValidUri($abs) && !$blazies->get('image.valid')) {
        $blazies->set('image.uri', $abs);
      }

      // Prevents 404 warning when video thumbnail missing for a reason.
      if (!BlazyFile::isExternal($uri)) {
        if ($dimensions = @getimagesize($abs)) {
          [$width, $height] = $dimensions;
        }
      }
    }

    // Since 2.17, the last two standing settings along with URI, now gone for
    // good into blazies object.
    $check[$_width] = $width;
    $check[$_height] = $height;

    // Sometimes they are string, cast them integer to reduce JS logic.
    self::toInt($check, $_width, $_height);

    // Defines original dimensions.
    $data = ['width' => $check[$_width], 'height' => $check[$_height]];

    // Image styles might be left empty, and aspect ratio is used.
    if ($fluid && !$blazies->is('unstyled')) {
      $dims = $data;
      $dims['ratios'] = $blazies->get('css.ratio');

      // The result is normally used for non-inline style, via CSS rules.
      $data['fluid'] = Ratio::fluid($dims);
    }

    // The result is normally used for inline style via padding hacks.
    $data['ratio'] = Ratio::compute($data);

    // If initial call, used by EZ, etc.
    if ($initial || !$blazies->get('first.width')) {
      $blazies->set('first', $data, TRUE);
    }

    // Only if not cropped uniformly.
    if (!$blazies->is('dimensions')) {
      $blazies->set('image', $data, TRUE);
    }

    // In case `image_style` is not provided.
    $blazies->set('image.original', $data, TRUE);
    return $data;
  }

  /**
   * Returns the image item out of File entity, ER, etc., or just $settings.
   *
   * @param object $object
   *   The optional Media, File entity, or ER, etc. to get image item from.
   * @param array $settings
   *   The optional settings.
   *
   * @return object|null
   *   The object of image item, or NULL.
   *
   * @todo simplify this, like everything else. An obvious confusion here.
   * @todo return image item directly without settings.
   */
  public static function fromAny($object, array &$settings = []): ?object {
    $blazies = Internals::verify($settings);
    $output  = $uri = NULL;

    // If Media entity, we must have a File entity, and likely ImageItem.
    if ($object instanceof MediaInterface) {
      $entity = $object;
    }
    else {
      // Extracts File entity from any object or settings, if applicable.
      // Node, EntityReferenceRevisionsItem, etc.
      // We do not come from BlazyFileFormatter, and co, here on. Instead
      // called by BlazyFilter file upload and legacy BlazyViewsFieldFile.
      $entity = BlazyFile::item($object, $settings);

      if (BlazyFile::isFile($entity)
        && $factory = Internals::service('image.factory')) {
        if ($output = self::fakeFromFactory($blazies, $entity, $factory)) {
          $uri = $output->uri;
        }
      }
    }

    // Called by entity formatters, excluding file.
    if (empty($output)) {
      $options = [
        'entity'   => $entity,
        'source'   => $entity == $object ? NULL : $object,
        'settings' => $settings,
      ];

      // We may have a Media entity, etc.
      $output = self::fromContent($options);
    }

    // Final URI check.
    $uri = $uri ?: BlazyFile::uri($output, $settings);

    if ($uri) {
      $blazies->set('image.uri', $uri);
    }

    return $output;
  }

  /**
   * Returns TRUE if an ImageItem.
   */
  public static function isImage($item): bool {
    return $item instanceof ImageItem;
  }

  /**
   * Returns the image item from any sources, if available.
   *
   * PHP 7.2 accepts object. D8 >= PHP 7.3. Not good for D7 backport.
   */
  public static function item($item = NULL, array $options = [], $name = NULL): ?object {
    return self::isValidItem($item) ? $item : self::fromContent($options, $name);
  }

  /**
   * Returns the image item from any sources, if available.
   *
   * This block is a bit scary yet it is a more organized way to extract Image
   * item from various sources in tandem with custom settings.image previously
   * scattered with if-else. This has saved more than 60 lines, and two methods:
   * ::fromMedia(), already gone. Can be better.
   */
  public static function fromContent(array $options, $name = NULL): ?object {
    $settings = Internals::toHashtag($options);
    $blazies  = $settings['blazies'] ?? NULL;
    $poster   = $settings['image'] ?? NULL;
    $poster   = $blazies ? $blazies->get('field.formatter.image', $poster) : $poster;
    $name     = $name ?: $poster;

    // If poster is not defined, use the source_field or thumbnail property.
    // Title is NULL from thumbnail, likely core bug, so use source.
    if ($blazies && !$name && $source = $blazies->get('media.source')) {
      $name = $source == 'image' ? $blazies->get('media.source_field') : 'thumbnail';
    }

    $func = function ($key, $property) use ($options) {
      $object = ($options[$key] ?? NULL);
      if ($object instanceof ContentEntityInterface
        && $object->hasField($property)) {
        $item = $object->get($property)->first();
        $valid = self::isImage($item);

        // Media embedded inside Paragraph item as defined by settings.image,
        // basically drilling down nested entities here to find the gold.
        if ($item) {
          if (!$valid && $entity = ($item->entity ?? NULL)) {
            if ($entity instanceof ContentEntityInterface
              && $entity->hasField('thumbnail')) {
              $item = $entity->get('thumbnail')->first();
            }
          }

          // For Remote video, it has meaningful label from OEmbed, OOTB.
          // @phpstan does not get alias self::isImage().
          if ($item instanceof ImageItem && property_exists($item, 'title')) {
            if (trim($item->title ?? '') == '') {
              $item->title = $object->label();
            }
          }
        }

        // @phpstan does not get alias self::isImage().
        return $item instanceof ImageItem ? $item : NULL;
      }
      return NULL;
    };

    // \Drupal\paragraphs\Entity\Paragraph, Media, Node, etc.
    $item = $func('entity', $name) ?: $func('source', $name);
    $item = $name ? $item : NULL;
    if (!$item) {
      $item = $func('entity', 'thumbnail') ?: $func('source', 'thumbnail');
    }

    return $item;
  }

  /**
   * Checks if we have image item.
   *
   * Both ImageItem and fake stdClass are valid, no problem.
   */
  public static function isValidItem($item): bool {
    $item = is_array($item) ? Internals::toHashtag($item, 'item', NULL) : $item;
    return is_object($item) && (isset($item->uri) || isset($item->target_id));
  }

  /**
   * Prepares URLs, placeholder, and dimensions for an individual image.
   *
   * Respects a few scenarios:
   * 1. Blazy Filter or unmanaged file with/ without valid URI.
   * 2. Hand-coded image_url with/ without valid URI.
   * 3. Respects first_uri without image_url such as colorbox/zoom-like.
   * 4. File API via field formatters or Views fields/ styles with valid URI.
   * If we have a valid URI, provides the correct image URL.
   * Otherwise leave it as is, likely hotlinking to external/ sister sites.
   * Hence URI validity is not crucial in regards to anything but #4.
   * The image will fail silently at any rate given non-expected URI.
   *
   * @param array $settings
   *   The given settings being modified.
   * @param object $item
   *   The image item.
   * @param string $uri
   *   The image uri.
   *
   * @requires CheckItem::unstyled()
   * @requires self::styles()
   */
  public static function prepare(array &$settings, $item = NULL, $uri = NULL): void {
    // Problems: the audio/ video poster is not synced. The root cause, local
    // media is not directly managed by theme_blazy() aka outside the workflow,
    // it is an embedded field. The correct solution is to call this method
    // before working with local media. They won't re-enter this method again.
    $blazies = $settings['blazies']->reset($settings);
    $uri = $uri ?: $blazies->get('image.uri');

    // Bailout if no URI.
    if (!$uri) {
      return;
    }

    // Provides original image dimensions.
    self::dimensions($settings, $item, $uri, FALSE);

    // Provides transformed image dimensions regardless unstyled so to have
    // correct dimensions at lightboxes, thumbnails, etc.
    self::transformed($settings, $uri);

    // Provides ResponsiveImage dimensions and styles, if any.
    BlazyResponsiveImage::transformed($settings);

    // Provides SVG dimensions, if any.
    BlazySvg::dimensions($settings, $uri);
    Internals::tokenize($blazies);
  }

  /**
   * Checks for Image styles at container level once, except for multi-styles.
   *
   * @todo remove for BlazyManager::imageStyles().
   */
  public static function styles(array &$settings, $multiple = FALSE): void {
    if ($manager = Internals::service('blazy.manager')) {
      $manager->imageStyles($settings, $multiple);
    }
  }

  /**
   * Extracts common data from a fake or real image item object.
   *
   * The best reason to remove ImageItem references is this pingpong.
   * Plan for 3.x:
   *   - Keep fake image item as array, no need to be an object.
   *   - Convert real ImageItem to an array when found.
   *   - Store both as just array into blazies.image.
   *
   * Since 2.17, a reliance on ImageItem has been gradually removed like seen at
   * Lightbox, at least made a fallback, no longer the dominance.
   */
  public static function toArray($item): array {
    $data = [];

    // A fake ImageItem has a uri and target_id.
    if (isset($item->uri)) {
      return (array) $item;
    }
    // A real ImageItem has a target_id, but no URI.
    elseif (isset($item->target_id)) {
      $uri = BlazyFile::uri($item);
      $data = ['uri' => $uri];

      foreach (BlazyDefault::imageProperties() as $key) {
        if (isset($item->{$key})) {
          $data[$key] = $item->{$key};
        }
      }
    }

    return $data;
  }

  /**
   * A wrapper for ImageStyle::transformDimensions().
   *
   * @param object $style
   *   The given image style.
   * @param array|object $config
   *   The data config: width, height, and uri, or $blazies as config source.
   * @param string $uri
   *   The optional URI if differs from main image, such as thumbnail URI.
   */
  public static function transformDimensions($style, $config, $uri = NULL): array {
    $fluid  = FALSE;
    $ratios = [];

    // Default non-API source:
    if (is_array($config)) {
      $uri    = $uri ?: ($config['uri'] ?? '');
      $width  = $config['width'] ?? NULL;
      $height = $config['height'] ?? NULL;
    }
    // A convenient API source, must be original sizes:
    else {
      $fluid  = $config->is('fluid');
      $ratios = $config->get('css.ratio');
      $uri    = $uri ?: ($config->get('image.uri') ?: $config->get('first.uri'));
      $width  = $config->get('image.original.width') ?: $config->get('first.width');
      $height = $config->get('image.original.height') ?: $config->get('first.height');
    }

    $dim = ['width' => $width, 'height' => $height];

    // Funnily $uri is ignored at all core image effects.
    if ($style) {
      $style->transformDimensions($dim, $uri);
    }

    // Sometimes they are string, cast them integer to reduce JS logic.
    self::toInt($dim, 'width', 'height');

    if ($fluid) {
      $info = $dim;
      $info['ratios'] = $ratios;
      $fluid = Ratio::fluid($info);
    }

    // Keys here are hard-coded, so to be inherited by children as intended.
    // See self::dimensions().
    return [
      'width'  => $dim['width'],
      'height' => $dim['height'],
      'ratio'  => Ratio::compute($dim),
      'fluid'  => $fluid,
    ];
  }

  /**
   * Returns image URL with an optional image style.
   *
   * Addressed various sources:
   * - URL which should not be styled: animated gif, apng, svg, etc.
   * - UGC image URL, with likely invalid URI due to hard-coded markdown, etc.
   * - Responsive image vs. regular image style.
   *
   * @requires \Drupal\blazy\internals\Internals::prepare()
   *
   * @see self::prepare()
   * @see self::background()
   * @see BlazyResponsiveImage::background()
   *
   * @todo remove fallbacks after another check, also settings after migration.
   */
  public static function toUrl(array $settings, $style = NULL, $uri = NULL): string {
    $blazies = $settings['blazies'];
    $uri     = $uri ?: $blazies->get('image.uri', $settings['uri'] ?? '');
    $valid   = BlazyFile::isValidUri($uri);
    $styled  = $valid && !$blazies->is('unstyled');
    $style   = $styled ? $style : NULL;
    $url     = $settings['image_url'] ?? '';
    $url     = $blazies->get('image.url') ?: $url;

    $options = [
      'unsafe' => $blazies->is('unsafe'),
      'url' => $url,
      'use_data_uri' => $blazies->filter('use_data_uri'),
    ];

    return self::url($uri, $style, $options);
  }

  /**
   * Returns image URL with an optional image style.
   */
  public static function url($uri, $style = NULL, array $options = []): string {
    $unsafe   = $options['unsafe'] ?? TRUE;
    $data_uri = $options['use_data_uri'] ?? FALSE;
    $url      = BlazyFile::transformRelative($uri, $style, $options);

    // Just in case, an attempted kidding gets in the way, relevant for UGC.
    // @todo re-check to completely remove data URI.
    if ($url && $unsafe) {
      $url = Sanitize::url($url, $data_uri);
    }

    return $url ?: '';
  }

  /**
   * Returns data to provide fake image item of file entity via ImageFactory.
   */
  private static function fromFile($file, $factory, $alt = NULL, $title = NULL): array {
    // Might be a video/ audio file URI, not just image.
    // @todo recheck not available beyond formatters, such as View Fields:
    // $item = $entity->_referringItem;
    $check = $file->getFileUri();

    if ($image = $factory->get($check)) {
      /** @var \Drupal\file\Entity\File $file */
      [$type] = explode('/', $file->getMimeType(), 2);

      // Including image/svg+xml.
      // ALT and TITLE might be hand-coded from BlazyFilter, and so meaningful.
      // @todo recheck && $image->isValid() and put it back if any issues.
      // @todo figure out some SVG invalid when accessed from non-formatters
      // like BlazyViewsFieldFile.
      if ($type == 'image') {
        $name = $file->getFilename();
        return [
          'uri'       => $file->getFileUri(),
          'target_id' => $file->id(),
          'alt'       => $alt ?: $name,
          'title'     => $title ?: '',
          'width'     => $image->getWidth(),
          'height'    => $image->getHeight(),
          'type'      => 'image',
          'entity'    => $file,
        ];
      }
    }
    return [];
  }

  /**
   * Returns data to provide fake image item of file entity via ImageFactory.
   *
   * @todo remove ImageItem, fake or real, at 3.x. No longer neccessary with
   * $blazies as object as planned at BlazyMedia since 2.6.
   */
  private static function fakeFromFactory($blazies, $file, $factory): ?object {
    $alt = $blazies->get('image.alt');
    $title = $blazies->get('image.title');

    if ($data = self::fromFile($file, $factory, $alt, $title)) {
      $dims = ['width' => $data['width'], 'height' => $data['height']];

      // @todo move it out of here for self::toArray():
      $blazies->set('image', $data, TRUE)
        ->set('image.original', $dims, TRUE);

      // @todo remove this pingpong at 3.x:
      $item = $blazies->toImage($data);
      $blazies->set('image.item', $item);

      return $item;
    }

    return NULL;
  }

  /**
   * Returns the image style if it contains crop effect.
   *
   * @param object $style
   *   The image style to check for.
   *
   * @return object
   *   Returns the image style instance if it contains crop effect, else NULL.
   */
  private static function getCrop($style): ?object {
    $id = $style->id();

    if (!isset(static::$crop[$id])) {
      $output = NULL;

      foreach ($style->getEffects() as $effect) {
        if (strpos($effect->getPluginId(), 'crop') !== FALSE) {
          $output = $style;
          break;
        }
      }
      static::$crop[$id] = $output;
    }
    return static::$crop[$id];
  }

  /**
   * Converts dimensions to integer unless empty.
   */
  private static function toInt(array &$data, $width, $height): void {
    $data[$width] = empty($data[$width]) ? NULL : (int) $data[$width];
    $data[$height] = empty($data[$height]) ? NULL : (int) $data[$height];
  }

  /**
   * Provides result of self::transformDimensions().
   *
   * Image styles were provided once at the container level, but not dimensions
   * which may require URIs at item level. Previously these are scattered around
   * as required, now called once for all. Nothing loaded if not so configured.
   * Since Blazy:2.9, image style entity is loaded once at container level,
   * but might still be needed for adopted Image formatter by a Views style.
   *
   * @todo since done at container, it might also truble the unstyled per URI.
   * @todo remove `image` check after another check. Was needed to be undefined
   * to not conflict with Responsive image last time, till required. Also image
   * may be set once if cropped at self::cropDimensions().
   * URI is not available at container level, except for the first,
   * or when preload option is enabled, unless enforced in the far future.
   *
   * @requires self::styles()
   */
  private static function transformed(array &$settings, $uri): void {
    $blazies = $settings['blazies'];

    // GIF, etc. can be converted. We'll refine SVG, external URL down below.
    // For now, only data URI is out of question.
    if (!$blazies->is('data_uri')) {
      self::transformedInternal($settings, $uri);
    }

    // External and unstyled image urls.
    if (!$blazies->get('image.url')) {
      $style = $blazies->get('image.style');
      $url = self::toUrl($settings, $style, $uri);
      $blazies->set('image.url', $url);
    }
  }

  /**
   * Provides result of self::transformDimensions() for internal urls.
   */
  private static function transformedInternal(array &$settings, $uri): void {
    $blazies = $settings['blazies'];
    foreach (BlazyDefault::imageStyles() as $key) {
      if ($style = $blazies->get($key . '.style')) {

        // @todo recheck if to disable for external URL upstream.
        $data = self::transformDimensions($style, $blazies, $uri);
        $blazies->set($key, $data, TRUE);

        // SVG and external don't convert, exclude them.
        if (!$blazies->is('svg') && !$blazies->is('external')) {
          $url = self::toUrl($settings, $style, $uri);
          $blazies->set($key . '.url', $url);
        }

        // To avoid double checks.
        if ($key == 'image') {
          $blazies->set('cache.metadata.tags', $style->getCacheTags(), TRUE);
        }
      }
    }
  }

}
