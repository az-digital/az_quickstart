<?php

namespace Drupal\blazy\Plugin\Filter;

use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;
// @todo use Drupal\media\MediaInterface;
use Drupal\blazy\Blazy;
use Drupal\blazy\BlazyDefault as Defaults;
use Drupal\blazy\Field\BlazyElementTrait;
use Drupal\blazy\Media\BlazyFile as File;
use Drupal\blazy\Media\BlazyImage as Image;
use Drupal\blazy\internals\Internals;
// @todo use Drupal\blazy\Media\BlazyMedia;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base filter class.
 */
abstract class BlazyFilterBase extends TextFilterBase implements BlazyFilterInterface {

  use BlazyElementTrait;

  /**
   * The blazy admin service.
   *
   * @var \Drupal\blazy\Form\BlazyAdminInterface
   */
  protected $blazyAdmin;

  /**
   * The blazy oembed service.
   *
   * @var \Drupal\blazy\Media\BlazyOEmbedInterface
   */
  protected $blazyOembed;

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

    $instance->blazyAdmin = $container->get('blazy.admin');
    $instance->blazyOembed = $container->get('blazy.oembed');
    $instance->svgManager = $container->get('blazy.svg');

    return $instance;
  }

  /**
   * Returns the main settings.
   *
   * @param string $text
   *   The provided text.
   *
   * @return array
   *   The main settings for current filter.
   */
  protected function buildSettings($text) {
    $config = $this->settings;
    $settings = &$this->settings;
    $settings += Defaults::lazySettings();

    $blazies = $this->manager->verifySafely($settings);

    $plugin_id = $this->getPluginId();
    $id = AttributeParser::getId($plugin_id);

    $definitions = $this->entityFieldManager->getFieldDefinitions('media', 'remote_video');
    $is_media_library = $definitions && isset($definitions['field_media_oembed_video']);

    $namespace = static::$namespace;

    $blazies->set('css.id', $id)
      ->set('is.filter', TRUE)
      ->set('is.unsafe', TRUE)
      ->set('is.media_library', $is_media_library)
      ->set('libs.filter', TRUE)
      ->set('filter.' . $namespace, $config)
      ->set('filter.plugin_id', $plugin_id)
      ->set('item.id', static::$itemId)
      ->set('item.prefix', static::$itemPrefix)
      ->set('item.caption', static::$captionId)
      ->set('item.shortcode', static::$shortcode)
      ->set('namespace', $namespace);

    $this->init($settings, $text);

    // Allows sub-modules to add return type hints.
    if (method_exists($this, 'preSettings')) {
      $this->preSettings($settings, $text);
    }

    $this->manager->preSettings($settings);

    $unwrap = static::$namespace != 'blazy';
    $blazies->set('lightbox.gallery_id', $id)
      ->set('no.item_container', $unwrap);

    $this->postSettings($settings);
    $this->manager->postSettings($settings);

    $this->manager->moduleHandler()->alter($plugin_id . '_settings', $settings, $this->settings);
    $this->manager->postSettingsAlter($settings);

    return $settings;
  }

  /**
   * Build the field item list using the node ID and field_name.
   */
  protected function formatterSettings(array &$settings, $attribute): ?object {
    [$entity_type, $id, $field_name, $field_image] = array_pad(array_map('trim', explode(":", $attribute, 4)), 4, NULL);

    $list = NULL;
    if (empty($field_name)) {
      return $list;
    }

    $entity  = $this->manager->load($id, $entity_type);
    $blazies = $settings['blazies'];
    $id      = (int) $id;

    if ($entity && $entity->hasField($field_name)) {
      $bundle = $entity->bundle();
      $list   = $entity->get($field_name);
      $count  = count($list);

      if ($list && $count > 0) {
        $definition = $list->getFieldDefinition();
        $field_type = $definition->get('field_type');
        $field_settings = $definition->get('settings');
        $handler = $field_settings['handler'] ?? NULL;
        $strings = ['link', 'string', 'string_long'];
        $texts = ['text', 'text_long', 'text_with_summary'];

        $settings['image'] = $field_image;

        // @todo remove most of these, except few.
        $blazies->set('bundles.' . $bundle, $bundle, TRUE)
          ->set('count', $count)
          ->set('total', $count)
          ->set('entity.bundle', $bundle)
          ->set('entity.id', $id)
          ->set('entity.type_id', $entity_type)
          ->set('entity.instance', $entity)
          ->set('field.handler', $handler)
          ->set('field.name', $field_name)
          ->set('field.type', $field_type)
          ->set('field.settings', $field_settings)
          ->set('is.string', in_array($field_type, $strings))
          ->set('is.text', in_array($field_type, $texts));
      }
    }
    return $list;
  }

  /**
   * Returns the faked image item for the image, uploaded or hard-coded.
   *
   * @param array $build
   *   The content array being modified.
   * @param object $node
   *   The HTML DOM object.
   * @param int $delta
   *   The item index.
   */
  protected function buildImageItem(array &$build, &$node, $delta = 0): void {
    $settings = &$build['#settings'];
    $blazies  = $settings['blazies'];
    $attrs    = $blazies->get('item.raw_attributes', []);

    $build['#delta'] = $delta;
    if ($src = trim($attrs['src'] ?? '')) {
      if ($node->nodeName == 'img') {
        $this->getImageItemFromImageSrc($build, $node, $src);
      }
      elseif ($node->nodeName == 'iframe') {
        try {
          // Prevents invalid video URL (404, etc.) from screwing up.
          $this->getImageItemFromIframeSrc($build, $node, $src, $delta);
        }
        catch (\Exception $ignore) {
          // Do nothing, likely local work without internet, or the site is
          // down. No need to be chatty or harsh on this. Thumbnails will do.
        }
      }
    }

    // @todo remove all ImageItem references at 3.x for blazies as object.
    $item = $this->manager->toHashtag($build, 'item', NULL);

    // @todo remove all ImageItem references at 3.x for blazies as object.
    $build['#item'] = $item;

    // Might be extracted at BlazyOembed, but not always iframes here.
    // Extract ImageItem info and merge them all here for sure.
    if ($item && $data = Image::toArray($item)) {
      $blazies->set('image', $data, TRUE)
        // @todo remove this pingpong at 3.x:
        ->set('image.item', $item);
    }
  }

  /**
   * Gets the caption if available.
   *
   * @param array $build
   *   The content array being modified.
   * @param object $node
   *   The HTML DOM object.
   *
   * @return \DOMElement|null
   *   The HTML DOM object, or null if not found.
   *
   * @todo add return type after sub-modules: ?\DOMElement.
   */
  protected function buildImageCaption(array &$build, &$node) {
    $settings = &$build['#settings'];
    $blazies = $settings['blazies'];
    $item = $this->getCaptionElement($node);

    // Sanitization was done by Caption filter when arriving here, as
    // otherwise we cannot see this figure, yet provide fallback.
    if ($item) {
      if ($text = $item->ownerDocument->saveXML($item)) {
        $markup = Xss::filter(trim($text), Defaults::TAGS);

        // Supports other caption source if not using Filter caption.
        if (empty($build['captions'])) {
          $build['captions']['alt'] = ['#markup' => $markup];
        }

        // Tells lightboxes to use this as is.
        if (($settings['box_caption'] ?? '') == 'inline') {
          $settings['box_caption'] = $markup;
        }

        $blazies->set('is.figcaption', TRUE);

        $this->cleanupImageCaption($build, $node, $item);
      }
    }
    return $item;
  }

  /**
   * Returns the expected caption DOMElement.
   *
   * @param object $node
   *   The HTML DOM object.
   *
   * @return \DOMElement|null
   *   The HTML DOM object, or null if not found.
   */
  protected function getCaptionElement($node): ?\DOMElement {
    if ($node->parentNode) {
      if ($node->parentNode->tagName === 'figure') {
        $caption = $node->parentNode->getElementsByTagName('figcaption');
        return ($caption && $caption->item(0)) ? $caption->item(0) : NULL;
      }

      return $this->getCaptionFallback($node);
    }
    return NULL;
  }

  /**
   * Returns the fallback caption DOMElement for Splide/ Slick, etc.
   *
   * @param object $node
   *   The HTML DOM object.
   *
   * @return \DOMElement|null
   *   The HTML DOM object, or null if not found.
   */
  protected function getCaptionFallback($node): ?\DOMElement {
    $caption = NULL;

    // @todo figure out better traversal with DOM.
    $parent = $node->parentNode->parentNode;
    if ($parent && $grandpa = $parent->parentNode) {
      if ($grandpa->parentNode) {
        $divs = $grandpa->parentNode->getElementsByTagName('div');
      }
      else {
        $divs = $grandpa->getElementsByTagName('div');
      }

      if ($divs) {
        foreach ($divs as $div) {
          $class = $div->getAttribute('class');
          if ($class == 'blazy__caption') {
            $caption = $div;
            break;
          }
        }
      }
    }
    return $caption;
  }

  /**
   * Cleanups image caption.
   */
  protected function cleanupImageCaption(array &$build, &$node, &$item): void {
    // Do nothing.
  }

  /**
   * Returns the real or faked image item from SRC, depending on the SRC.
   *
   * @param array $build
   *   The content array being modified: item, settings.
   * @param object $node
   *   The HTML DOM object.
   * @param string $src
   *   The corrected SRC value.
   *
   * @todo refactor to move ImageItem downstream, or remove it completely.
   */
  protected function getImageItemFromImageSrc(array &$build, $node, $src): void {
    $settings = &$build['#settings'];
    $blazies  = $settings['blazies'];
    $attrs    = $blazies->get('item.raw_attributes', []);
    $file     = NULL;
    $data_uri = FALSE;
    $uuid     = $attrs['data-entity-uuid'] ?? NULL;

    // 1. Data URI can only be seen if `Trust data URI` enabled, else empty.
    if (Blazy::isDataUri($src)) {
      $uri = $src;
      $data_uri = TRUE;

      // Data URI is just an URI, only monstrous.
      $blazies->set('image.uri', $uri)
        ->set('image.url', $uri)
        ->set('is.data_uri', TRUE)
        ->set('image.trusted', TRUE);
    }
    else {
      // 2. Uploaded files, external, etc. Might be NULL.
      // Attempts to get the correct URI with hard-coded URL if applicable, e.g:
      // /site/default/files/image.jpg into public://image.jpg.
      $uri = File::buildUri($src);

      if ($uri) {
        $blazies->set('entity.uuid', $uuid)
          ->set('image.uri', $uri);
        $file = File::item(NULL, $settings, $uri);
      }
    }

    // 3. Uploaded image has UUID with file API.
    if (File::isFile($file)) {
      $uuid = $uuid ?: $file->uuid();

      $blazies->set('entity.uuid', $uuid)
        ->set('image.trusted', TRUE);

      if ($item = Image::fromAny($file, $settings)) {
        $build['#item'] = $item;
      }
    }
    else {
      // 4. Manually hard-coded URL, external, has no UUID, nor file API.
      // URI validity is not crucial, URL is the bare minimum for Blazy to work.
      $uri = $uri ?: $src;

      if ($uri) {
        $data = ['uri' => $uri, 'entity' => $file];
        $blazies->set('image', $data, TRUE);

        $data = $blazies->get('image');
        $build['#item'] = $blazies->toImage($data);
      }

      // 5. External URL, or unmanaged file URL, excluding data URI.
      // Do not pass this file system URI into fake image item.
      if (!$data_uri && !File::isValidUri($uri)) {
        // At least provide root URI to figure out image dimensions.
        $uri = mb_substr($src, 0, 4) === 'http' ? $src : $this->root . $src;
        $blazies->set('image.uri_root', $uri);
      }
    }
  }

  /**
   * Returns the faked image item from SRC.
   *
   * @param array $build
   *   The content array being modified: item, settings.
   * @param object $node
   *   The HTML DOM object.
   * @param string $src
   *   The corrected SRC value.
   * @param int $delta
   *   The delta.
   */
  protected function getImageItemFromIframeSrc(array &$build, &$node, $src, $delta = 0): void {
    $settings = &$build['#settings'];
    $blazies  = $settings['blazies'];

    // @todo figure out to not hard-code `field_media_oembed_video`.
    $media = NULL;
    if ($blazies->is('media_library')) {
      $media = $this->manager->loadByProperty(
        'field_media_oembed_video.value',
        $src,
        'media'
      );
    }

    // Runs after type, width and height set, if any, to not recheck them.
    $build['#entity'] = $media;
    $this->blazyOembed->build($build);
  }

  /**
   * Provides the shortcode ITEM|SLIDE attributes, and caption. Not IMG/IFRAME.
   */
  protected function buildItemAttributes(array &$build, $node, $delta = 0): void {
    $sets    = &$build['#settings'];
    $blazies = $sets['blazies'];

    // In case we forgot what we were talking about, add a reminder.
    if (in_array($node->tagName, ['item', 'slide'])) {
      $blazies->set('is.shortcode', TRUE);

      foreach (['title', 'caption'] as $key) {
        if ($caption = $node->getAttribute($key)) {
          $k = $key == 'caption' ? 'alt' : $key;
          $build['captions'][$k] = ['#markup' => $this->filterHtml($caption)];
          $blazies->set('image.' . $k, strip_tags($caption))
            ->set('image.shortcode', TRUE);
          $node->removeAttribute($key);
        }
      }

      // These are shortcode attributes for grid ITEM, or SLIDE.
      if ($attrs = AttributeParser::getAttribute($node)) {
        // Might be consumed directly by sub-modules.
        $attrs = Blazy::sanitize($attrs);
        $this->shortcodeItemAttributes($build, $node, $blazies, $attrs);
      }
    }
  }

  /**
   * Provides the shortcode ITEM|SLIDE attributes, and caption. Not IMG/IFRAME.
   *
   * @todo refine all these against sub-modules.
   */
  protected function shortcodeItemAttributes(array &$build, $node, $blazies, array $attrs): void {
    // Move it to .grid__content for better displays like .well/ .card.
    if ($classes = $attrs['class'] ?? '') {
      // This is blazy .grid__content since theme_blazy() has none:
      if ($node->tagName == 'item') {
        $blazies->set('grid.item_content_attributes.class', $classes);
      }
      else {
        // Consumed at $manager::toBlazy() to pass back to theme_blazy().
        $blazies->set('item.wrapper_attributes.class', $classes);
      }

      unset($attrs['class']);
    }

    // This is for blazy .grid attributes, not .grid__content:
    if ($node->tagName == 'item') {
      $blazies->set('grid.item_attributes', $attrs);
    }
    else {
      // Processed at [slick|splide]_slide for their .slide element.
      $blazies->set('item.attributes', $attrs);
    }
  }

  /**
   * Provides the media IMG|IFRAME attributes w/o shortcodes ITEM|SLIDE.
   */
  protected function buildMediaAttributes(array &$build, $node, $delta = 0): void {
    $settings = &$build['#settings'];
    $blazies  = $settings['blazies'];
    $tag      = $node->nodeName;
    $attrs    = AttributeParser::getAttribute($node);

    if (!$attrs) {
      return;
    }

    // Prevents blur IMG from screwing up the expected image SRC.
    if ($src = $attrs['src'] ?? NULL) {
      $use_data_uri = $this->settings['use_data_uri'] ?? FALSE;
      $src = AttributeParser::getValidSrc($node, $use_data_uri);

      // Iframe with data: alike scheme is a serious kidding, strip it early.
      if ($tag == 'iframe') {
        $src = $this->blazyOembed->checkInputUrl($settings, $src);
      }
      $attrs['src'] = $src;
    }

    // Put raw attributes into a pandora box.
    $blazies->set('item.raw_attributes', $attrs);

    // Normally consumed default IMG attributes, ignoring IFRAME, no problem.
    // These dups are required to build image styles, ratio, etc.
    foreach (['width', 'height', 'alt', 'title'] as $key) {
      if ($value = $attrs[$key] ?? NULL) {
        // Might be set by shortcode which has more meaningful intentions.
        if (!$blazies->get('image.' . $key)) {
          $blazies->set('image.' . $key, $value);
        }
      }
      // Who knows unsetting NULL would be deprecated, like trim(), etc.
      unset($attrs[$key]);
    }

    // Do not pass SRC into theme_image() so that lazy load works.
    // Also the width and height so to make data-responsive|image-style works.
    // BlazyFilter doesn't offer UI for loading attribute, sub-modules do,
    // yet respect the editor textarea as the only UI better than global UI.
    // Might work agaisnt the offered UI, but no biggies for now.
    // @todo recheck anything against the grand design.
    foreach (['data-src', 'src'] as $key) {
      // Who knows unsetting NULL would be deprecated, like trim(), etc.
      unset($attrs[$key]);
    }

    // Ensures relevant attributes are passed through.
    $type = 'image';
    $safe_attrs = Blazy::sanitize($attrs);
    if ($tag == 'img') {
      $blazies->set('image.attributes', $safe_attrs);
    }
    elseif ($tag == 'iframe') {
      $type = 'video';

      Internals::toPlayable($blazies)
        ->set('media.bundle', 'remote_video');

      $blazies->set('iframe.attributes', $safe_attrs);
    }
    $blazies->set('media.type', $type);
  }

  /**
   * Returns the item settings for the current $node.
   *
   * @param array $build
   *   The settings being modified.
   * @param object $node
   *   The HTML DOM object.
   * @param int $delta
   *   The item index.
   *
   * @return bool
   *   TRUE if it has different image style from the selected option.
   */
  protected function buildItemSettings(array &$build, $node, $delta = 0): bool {
    $settings   = &$build['#settings'];
    $blazies    = $settings['blazies'];
    $ui_style   = $settings['image_style'] ?? NULL;
    $ui_restyle = $settings['responsive_image_style'] ?? NULL;
    $attrs      = $blazies->get('item.raw_attributes', []);
    $update     = FALSE;

    // Set an image style based on node data properties.
    // See https://www.drupal.org/project/drupal/issues/2061377,
    // https://www.drupal.org/project/drupal/issues/2822389, and
    // https://www.drupal.org/project/inline_responsive_images.
    // Compare with UI if any difference before re-updating.
    if ($style = $attrs['data-image-style'] ?? NULL) {
      if ($style != $ui_style) {
        $update = TRUE;
        $settings['image_style'] = $style;
      }
    }

    if ($style = $attrs['data-responsive-image-style'] ?? NULL) {
      if ($blazies->is('resimage') && $style != $ui_restyle) {
        $update = TRUE;
        $settings['responsive_image_style'] = $style;
      }
    }

    return $update;
  }

  /**
   * Build the individual item content, just IMG/IFRAME, not ITEM/SLIDE.
   *
   * @param array $build
   *   The content array being modified.
   * @param object $node
   *   The HTML DOM object.
   * @param int $delta
   *   The item index.
   */
  protected function buildItemContent(array &$build, $node, $delta = 0): void {
    // To minimize dups, or misses, for something obvious.
    $build['#delta'] = $delta;

    // Provides IMG/IFRAME attributes.
    $this->buildMediaAttributes($build, $node, $delta);

    // Provides individual item settings.
    $update = $this->buildItemSettings($build, $node, $delta);

    // Extracts image item from SRC attribute.
    $this->buildImageItem($build, $node, $delta);

    // Extracts image caption if available.
    $this->buildImageCaption($build, $node);

    // Checks for image styles at individual items, normally set at container.
    // Responsive image is at item level due to requiring URI detection.
    // Must have an URI set above.
    if ($update) {
      $settings = &$build['#settings'];
      $blazies  = $settings['blazies'];

      $blazies->set('is.multistyle', TRUE);
      $this->manager->imageStyles($settings, TRUE);
    }
  }

  /**
   * Provides media switch form.
   */
  protected function mediaSwitchForm(array &$form): void {
    $lightboxes = $this->manager->getLightboxes();

    $form['media_switch'] = [
      '#type' => 'select',
      '#title' => $this->t('Media switcher'),
      '#options' => [
        'media' => $this->t('Image to iframe'),
      ],
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->settings['media_switch'] ?? '',
      '#description' => $this->t('<ul><li><b>Image to iframe</b> will play video when toggled.</li><li><b>Image to lightbox</b> (Colorbox, Splidebox, PhotoSwipe, Slick Lightbox, Zooming, Intense, etc.) will display media in lightbox.</li></ul>Both can stand alone or grouped as a gallery. To build a gallery, use the grid shortcodes.'),
    ];

    if (!empty($lightboxes)) {
      foreach ($lightboxes as $lightbox) {
        $name = Unicode::ucwords(str_replace('_', ' ', $lightbox));
        $form['media_switch']['#options'][$lightbox] = $this->t('Image to @lightbox', ['@lightbox' => $name]);
      }
    }

    $styles = $this->blazyAdmin->getResponsiveImageOptions()
      + $this->blazyAdmin->getEntityAsOptions('image_style');

    $form['hybrid_style'] = [
      '#type' => 'select',
      '#title' => $this->t('(Responsive) image style'),
      '#options' => $styles,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->settings['hybrid_style'] ?? '',
      '#description' => $this->t('Fallback (Responsive) image style when <code>[data-image-style]</code> or <code>[data-responsive-image-style]</code> attributes are not present, see https://drupal.org/node/2061377.'),
    ];

    $form['box_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Lightbox (Responsive) image style'),
      '#options' => $styles,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->settings['box_style'] ?? '',
    ];

    $form['box_media_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Lightbox media style'),
      '#options' => $styles,
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->settings['box_media_style'] ?? '',
    ];

    $captions = $this->blazyAdmin->getLightboxCaptionOptions();
    unset($captions['entity_title'], $captions['custom']);
    $form['box_caption'] = [
      '#type' => 'select',
      '#title' => $this->t('Lightbox caption'),
      '#options' => $captions + ['inline' => $this->t('Caption filter')],
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $this->settings['box_caption'] ?? '',
      '#description' => $this->t('Automatic will search for Alt text first, then Title text. <br>Image styles only work for uploaded images, not hand-coded ones. Caption filter will use <code>data-caption</code> normally managed by Caption filter, will not work for shortcode without [item] element.'),
    ];
  }

}
