<?php

namespace Drupal\blazy\Views;

// @todo enable use Drupal\blazy\Field\BlazyElementTrait;
use Drupal\Core\Url;
use Drupal\blazy\Blazy;
use Drupal\blazy\internals\Internals;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A base for blazy views integration to support media/ images.
 */
abstract class BlazyStyleBase extends BlazyStyleVanilla implements BlazyStyleBaseInterface {

  // @todo enable if similar to field formatters:
  // use BlazyElementTrait;
  /**
   * The blazy media service.
   *
   * @var \Drupal\blazy\Media\BlazyMediaInterface
   */
  protected $mediaManager;

  /**
   * The svg manager service.
   *
   * @var \Drupal\blazy\Media\Svg\SvgInterface
   */
  protected $svgManager;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->mediaManager = $container->get('blazy.media');
    $instance->svgManager = $container->get('blazy.svg');

    return $instance;
  }

  /**
   * Checks if we can work with this formatter, otherwise no go if flattened.
   */
  protected function getImageArray($row, $index, $field_image): array {
    if ($image = $this->getFieldRenderable($row, $index, $field_image)) {

      // Just to be sure, replace raw with the found image item.
      if ($item = $this->getImageItem($image)) {
        $image['raw'] = $item;
      }

      // Known image formatters: Blazy, Image, etc. which provides ImageItem.
      // Else dump Video embed thumbnail/video/colorbox as is.
      if ($item || isset($image['rendered'])) {
        return $image;
      }
    }
    return [];
  }

  /**
   * Get the image item to work with out of this formatter.
   *
   * All this mess is because Views may render/flatten images earlier.
   */
  protected function getImageItem($image): ?object {
    $item = NULL;

    if ($rendered = ($image['rendered'] ?? [])) {
      // Image formatter.
      $item = $rendered['#item'] ?? NULL;

      // Blazy formatter, also supports multiple, `group_rows`.
      if ($build = ($rendered['#build'] ?? [])) {
        $item = $this->manager->toHashtag($build, 'item') ?: $item;
        $item = $build[0]['#item'] ?? $item;
      }
    }

    // Don't know other reasonable formatters to work with.
    return $this->isValidImageItem($item) ? $item : NULL;
  }

  /**
   * Returns the modified renderable image_formatter to support lazyload.
   */
  protected function getImageRenderable(array &$settings, $row, $index): array {
    $_image = $settings['image'] ?? NULL;
    if (!$_image) {
      return [];
    }

    $image    = $this->getImageArray($row, $index, $_image);
    $rendered = $image['rendered'] ?? [];
    $item     = $image['raw'] ?? NULL;

    // Supports 'group_rows' option.
    // @todo recheck if any side issues for not having raw key.
    $image['applicable'] = FALSE;
    if (!$rendered) {
      return $image;
    }

    // If the image has #item property, lazyload may work, otherwise skip.
    // This hustle is to lazyload tons of images -- grids, large galleries,
    // gridstack, mason, with multimedia/ lightboxes for free.
    /** @var \Drupal\image\Plugin\Field\FieldType\ImageItem $item */
    if ($this->isValidImageItem($item)) {
      $image['raw'] = $item;

      // Supports multiple image styles within a single view such as GridStack,
      // else fallbacks to the defined image style if available.
      if (empty($settings['image_style'])) {
        $settings['image_style'] = $rendered['#image_style']
          ?? $rendered['#style_name'] ?? '';
      }

      // Converts image formatter for blazy to reduce complexity with CSS
      // background option, and other options, and still lazyload it.
      $theme = $rendered['#theme']
        ?? $rendered['#build'][0]['#theme']
        ?? '';

      if ($theme && in_array($theme, ['blazy', 'image_formatter'])) {
        if ($theme == 'blazy') {
          $this->withBlazyFormatter($settings, $rendered, $index);
        }
        elseif ($theme == 'image_formatter') {
          $this->withImageFormatter($settings, $rendered, $index);
        }

        $image['applicable'] = TRUE;
      }
    }

    return $image;
  }

  /**
   * Provides a potential unique thumbnail different from the main image.
   *
   * Be sure to reset settings before calling this method:
   * $this->reset($sets);
   *
   * @todo remove the new param default NULL at/ by 3.x after sub-modules.
   */
  protected function getThumbnail(array &$sets, $row, $index, $field_caption = NULL): array {
    $name    = $sets['thumbnail'] ?? NULL;
    $blazies = $sets['blazies'];

    $blazies->set('is.reset', TRUE);

    // Thumbnail image is optional for tab navigation like.
    [
      'doable' => $doable,
      'item' => $item,
    ] = $this->getWorkableThumbnail($sets, $row, $name, $index);

    // Caption is optional for thumbed navigation only.
    $caption = [];
    if ($field_caption) {
      $caption = $this->getFieldRendered($index, $field_caption, FALSE, $row);
    }

    // Replace empty image item with the rendered output if not doable.
    if (!$doable && $name) {
      $item = $this->getFieldRendered($index, $name, FALSE, $row);
    }

    // If ($id = $blazies->get('thumbnail.id')) {
    // $sets['thumbnail_style'] = $id;
    // }
    // Even if multiple, only one thumbnail can exist.
    return $this->manager->getThumbnail($sets, $item, $caption);
  }

  /**
   * Extract image style and url from blazy image formatter.
   */
  protected function withBlazyFormatter(array &$settings, array $rendered, $index): void {
    // Pass Blazy field formatter settings into Views style plugin.
    // This allows richer contents such as multimedia/ lightbox for free.
    // Yet, ensures the Views style plugin wins over Blazy formatter,
    // such as with GridStack which may have its own breakpoints.
    $newbies   = $this->manager->toHashtag($rendered['#build']);
    $formatter = array_filter($newbies);
    $settings  = array_merge($formatter, array_filter($settings));

    // Reserves crucial blazy specific settings.
    Internals::preserve($settings, $formatter);

    // Each blazy delta is always 0 within a view, this makes it gallery.
    $blazies = $settings['blazies'];
    $blazies->merge($formatter['blazies']->storage());
    $blazies->set('delta', $index)
      ->set('is.gallery', !empty($settings['media_switch']));

    $tn  = $blazies->get('thumbnail.uri', 'x');
    $uri = $blazies->get('image.uri');

    // Views Media thumbnail may not have expected thumbnail URI, override.
    if ($uri && strpos($tn, 'media-icons') !== FALSE) {
      $style = NULL;
      if ($tn_style = $settings['thumbnail_style'] ?? NULL) {
        $style = $this->manager->load($tn_style, 'image_style');
        $uri = $style->buildUri($uri);
        $blazies->set('thumbnail.id', $tn_style);
      }

      $url = Blazy::url($uri, $style);

      $blazies->set('thumbnail.uri', $uri)
        ->set('thumbnail.url', $url)
        ->set('thumbnail.item', $rendered['#item']);
    }
  }

  /**
   * Extract image style and url from core image formatter.
   */
  protected function withImageFormatter(array &$settings, array $rendered, $index): void {
    $blazies = $settings['blazies'];

    // Deals with "link to content/image" by formatters.
    $url = $rendered['#url'] ?? '';

    // Checks if an object.
    if ($url instanceof Url) {
      $url = $url->setAbsolute()->toString();
    }

    // Prevent images from having absurd height when being lazyloaded.
    // Allows to disable it by _noratio such as enforced CSS background.
    $noratio = $settings['_noratio'] ?? FALSE;
    $settings['ratio'] = $blazies->is('noratio', $noratio) ? '' : 'fluid';

    if (empty($settings['media_switch']) && $url) {
      $settings['media_switch'] = 'content';
      $blazies->set('switch', 'content');
    }

    $blazies->set('delta', $index)
      ->set('entity.url', $url);
  }

  /**
   * Provides a workable thumbnail if any.
   *
   * Be sure to reset settings before calling this method:
   * $this->reset($sets);
   */
  private function getWorkableThumbnail(array &$sets, $row, $name, $index): array {
    if (!$name) {
      return ['doable' => FALSE, 'item' => NULL];
    }

    // Can only have one thumbnail even if multiple.
    // Supports core image formatter, the most sensible, and Blazy formatter.
    $blazies  = $sets['blazies'];
    $doable   = FALSE;
    $result   = $this->getFieldRenderable($row, 0, $name);
    $rendered = $result['rendered'] ?? [];
    $tn_style = $rendered['#image_style'] ?? $rendered['#style_name'] ?? NULL;
    $item     = $rendered['#item'] ?? NULL;
    $uri      = $rendered['#uri'] ?? NULL;
    $build    = $rendered['#build'] ?? [];

    // Might be group_rows, the first two are blazy, the last image_formatter.
    if (!$item) {
      $item = $build['#item'] ?? $build[0]['#item'] ?? $rendered['raw'] ?? NULL;
    }

    // If no URI, but we have an ImageItem.
    if (!$uri && is_object($item)) {
      $uri = Blazy::uri($item);
    }

    // Only if we have an URI.
    if ($uri) {
      $tn_uri = $uri;

      // Also set it as an image.uri for lazy load to work.
      if (!$blazies->get('image.uri')) {
        $blazies->set('image.uri', $uri);
      }

      // This allows a thumbnail different from the main stage, such as logos
      // thumbnails, and company buildings for the main stage.
      $style = NULL;

      if ($tn_style && !Internals::isSvg($tn_uri)) {
        if ($style = $this->manager->load($tn_style, 'image_style')) {
          $sets['thumbnail_style'] = $tn_style;
          // @todo recheck and remove $tn_uri = $style->buildUri($tn_uri);
        }
      }

      $tn_url = Blazy::url($tn_uri, $style);

      $blazies->set('thumbnail.id', $tn_style)
        ->set('thumbnail.uri', $tn_uri)
        ->set('thumbnail.url', $tn_url)
        ->set('thumbnail.item', $item);

      $doable = TRUE;
    }
    else {
      $doable = $blazies->get('image.uri') != NULL;
    }
    return ['doable' => $doable, 'item' => $item];
  }

}
