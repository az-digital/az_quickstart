<?php

namespace Drupal\blazy;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Url;
use Drupal\blazy\Theme\Lightbox;
use Drupal\blazy\internals\Internals;

/**
 * Implements a public facing blazy manager.
 *
 * A few modules re-use this: GridStack, Mason, Slick...
 */
class BlazyManager extends BlazyManagerBase implements BlazyManagerInterface, TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  protected static $namespace = 'blazy';

  /**
   * {@inheritdoc}
   */
  protected static $itemId = 'content';

  /**
   * {@inheritdoc}
   */
  protected static $itemPrefix = 'blazy';

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRenderBlazy', 'preRenderBuild'];
  }

  /**
   * {@inheritdoc}
   */
  public function getBlazy(array $build): array {
    $hashtags = array_keys(BlazyDefault::hashedProperties());

    foreach (BlazyDefault::themeProperties() as $key => $default) {
      $k = in_array($key, $hashtags) ? "#$key" : $key;
      $build[$k] = $this->toHashtag($build, $key, $default);
    }

    $item     = $this->toHashtag($build, 'item', NULL);
    $blazies  = $this->preBlazy($build, $item);
    $settings = $build['#settings'];
    $delta    = $build['#delta'] ?? $blazies->get('delta');

    // Since 2.17, theme_blazy() is more permissive, even if no URI is given,
    // so to be able to at least process the captions for markup consistency.
    // We'll bail out downstream if no URI is given, but not here.
    $content = [
      '#theme'       => 'blazy',
      '#delta'       => $delta,
      '#item'        => $item,
      '#image_style' => $settings['image_style'],
      '#uri'         => $blazies->get('image.uri'),
      '#build'       => $build,
      '#pre_render'  => [[$this, 'preRenderBlazy']],
    ];

    $this->moduleHandler->alter('blazy', $content, $settings);
    return $content;
  }

  /**
   * {@inheritdoc}
   */
  public function preRenderBlazy(array $element): array {
    $build = $element['#build'];
    unset($element['#build']);

    // Prepare the main image.
    $this->prepareBlazy($element, $build);

    // Fetch the newly modified settings with hashed key.
    $settings = &$element['#settings'];
    $blazies = $settings['blazies'];
    $switch = $blazies->get('switch');

    // Bail out if no URI is provided.
    if ($blazies->get('image.uri')) {
      // Disables linkable Pinterest, Twitter, etc.
      // @todo refine or excludes other providers that should not be linked.
      $linked = in_array($switch, ['link', 'content']) && Internals::linkable($blazies);

      // If Image linked to Content, or Link/ Plain text URL field.
      if ($linked) {
        $this->toLink($element, $blazies);
      }
      elseif ($blazies->is('lightbox')) {
        // Allows altering the lightbox item.
        $this->moduleHandler->alter('blazy_lightbox', $element);
        Lightbox::build($element);
      }
    }

    unset($build);
    return $element;
  }

  /**
   * Returns the contents using theme_field(), or theme_item_list().
   *
   * Blazy outputs can be formatted using either flat list via theme_field(), or
   * a grid of Field items or Views rows via theme_item_list().
   *
   * @param array $data
   *   The array containing: settings, children elements, or optional items.
   *
   * @return array
   *   The alterable and renderable array of contents.
   */
  public function build(array $data): array {
    $settings = $this->getBlazySettings($data);
    $blazies  = $settings['blazies'];

    // This #pre_render doesn't work if called from Views results, hence the
    // output is split either as theme_field() or theme_item_list().
    if ($blazies->is('grid')) {
      $build = $this->themeItemList($data, $settings, $blazies);
    }
    else {
      $build = $this->themeField($data, $settings);
    }

    $this->moduleHandler->alter('blazy_build', $build, $settings);
    return $build;
  }

  /**
   * Builds the Blazy outputs as a structured array ready for ::renderer().
   */
  public function preRenderBuild(array $element): array {
    $build = $element['#build'];
    unset($element['#build']);

    // Checks if we got some signaled attributes.
    $attributes = $element['#theme_wrappers']['container']['#attributes']
      ?? $element['#attributes'] ?? [];

    // Checks if we got some signaled attachments.
    $attachments = $this->toHashtag($build, 'attached');
    if ($attachments) {
      unset($build['#attached'], $build['attached']);
    }

    $settings = $build['#settings'];

    // Runs after settings.
    $items = $this->toElementChildren($build);

    // Take over elements for a grid display as this is all we need, learned
    // from the issues such as: #2945524, or product variations.
    // We'll selectively pass or work out $attributes not so far below.
    $element = $this->toGrid($items, $settings);

    if ($attributes) {
      // Signals other modules if they want to use it.
      // Cannot merge it into Grid (wrapper_)attributes, done as grid.
      // Use case: Product variations, best served by ElevateZoom Plus.
      if (isset($element['#ajax_replace_class'])) {
        $element['#container_attributes'] = Blazy::sanitize($attributes);
      }
      else {
        // Use case: VIS, can be blended with UL element safely down here.
        // The $attributes is merged with self::toGrid() ones here.
        $attrs = $this->merge($attributes, $element, '#attributes');
        $element['#attributes'] = Blazy::sanitize($attrs);
      }
    }

    // Sets attachments/ libraries, and container caches.
    $this->setAttachments($element, $settings, $attachments);
    unset($build);
    return $element;
  }

  /**
   * Build captions for both old image, or media entity.
   */
  protected function buildCaption(array $captions, $blazies, $prefix, $id = 'blazy'): array {
    $inline = $categories = $descriptions = $overlays = [];
    $_desc  = $prefix . 'description';
    $keys   = array_keys($captions);
    $keys   = array_combine($keys, $keys);
    $keys   = array_filter($keys, fn($k) => strpos($k, 'title') === FALSE, ARRAY_FILTER_USE_KEY);
    $single = count($keys) == 1;
    $ttag   = $blazies->get('item.title_tag', 'h2');

    // Supports multiple description fields.
    foreach ($captions as $key => $caption) {
      $css = $prefix . $key;
      if (strpos($key, 'title') !== FALSE) {
        $inline[$key] = $this->toHtml($caption, $ttag, $prefix . 'title');
      }
      elseif ($key == 'overlay') {
        $overlays[$key] = $this->toHtml($caption, 'div', $css);
      }
      elseif ($key == 'category') {
        $categories[$key] = $this->toHtml($caption, 'div', $css);
      }
      else {
        $key = str_replace('field_', '', $key);
        $css = str_replace('_', '-', $key);
        $css = $prefix . $css;

        // Merge alt, data, description in one description container.
        $nowrap = $single && isset($caption['#markup']);
        if (in_array($key, ['alt', 'data', 'description'])) {
          // Preserve old behaviors, but prevents similar classes.
          $key = $key == 'description' ? 'item' : $key;
          $css = $id == 'blazy' ? $_desc . '-' . $key : $_desc . '--' . $key;

          // @todo remove, might all be just NULL here.
          $css = $nowrap || $key == 'data' ? NULL : $css;

          $descriptions[$key] = $this->toHtml($caption, 'div', $css);
        }
        else {
          // Might be link, etc. here on.
          $inline[$key] = $this->toHtml($caption, 'div', $css);
        }
      }
    }

    // Merge multiple decsriptions to avoid too many siblings.
    if ($descriptions) {
      $inline['description'] = $this->toHtml($descriptions, 'div', $_desc);
    }

    $output = [];
    if ($inline = array_filter($inline)) {
      // Link is normally at the end of the day.
      if ($item = $inline['link'] ?? []) {
        unset($inline['link']);
        $inline['link'] = $item;
      }

      // Figcaption is more relevant for core filter captions under Figure.
      $tag = $blazies->is('figcaption') ? 'figcaption' : 'div';

      // Two caption types: inline and lightbox. Hence inline:
      $output  = ['inline' => $inline, 'tag' => $tag];
      $output += $categories;
    }

    $result = $output + $overlays;
    return array_filter($result);
  }

  /**
   * Build out (rich media) content.
   */
  private function buildContent(array &$element, array &$build): void {
    $settings = &$build['#settings'];
    $blazies  = $settings['blazies'];

    if (empty($build['content'])) {
      return;
    }

    // Update with the processed settings, only needed for video posters so far.
    // Since 2.17, replacing the current $settings was moved upstream at
    // \Drupal\blazy\Media\BlazyOEmbed::fromMedia(), not here.
    // What we do here is filling up $blazy with the processed image URL, etc.
    // The last is to account for the Use theme_blazy() option from sub-modules,
    // see Internals::toContent().
    $item  = $build['content'][0] ?? $build['content'];
    $blazy = $item['#settings'] ?? NULL;

    if ($blazy instanceof BlazySettings) {
      $this->mergeSettings('blazies', $settings, $blazy->storage());
    }

    // Ensures at least the library is attached before emptying anything below.
    // @todo defer heavy external sites' scripts into lazy loaded HTML?
    if ($attachments = $item['#attached'] ?? []) {
      $element['#attached'] = $this->merge($attachments, $element, '#attached');
    }

    // Supports HTML content for lightboxes as long as having image trigger.
    // Only limit to local media to not conflict with Image rendered by its
    // formatter option, Facebook, Twitter, etc.
    // Since 2.17, any content can be lightboxed as long as supported.
    // Only possible if having hires image via `Main stage` aka cross image,
    // and the lightbox is capable to display it.
    $image   = $blazies->get('field.formatter.image', $settings['image'] ?? NULL);
    $hires   = $blazies->is('hires', !empty($image));
    $hires   = $hires || $blazies->get('box_media.id');
    $richbox = $blazies->is('lightbox') && $blazies->is('richbox');

    if ($richbox && $hires) {
      // When SVG reaches here, it must be INLINE, and occupy content. However
      // for lightboxes SVG can be displayed as IMG even if INLINE, no problems.
      // Shortly, SVG does not need to be displayed as HTML content since
      // lightboxes is capable to display SVG as IMG just fine, any except?
      if (!$blazies->is('svg')) {
        $element['#lightbox_html'] = $build['content'];

        // This allows theme_blazy() to process it as workable media elements.
        // Putting this inside the block also respects inline SVG option.
        $build['content'] = [];
      }
    }
    else {
      // Exclude local audio/video, already lazy-loaded by theme_blazy().
      if (!$blazies->is('local_media')) {
        $unlazy   = Internals::isUnlazy($blazies);
        $media    = $blazies->get('lazy.html') && $blazies->get('media.id');
        $switch   = $blazies->get('switch');
        $provider = $blazies->get('media.provider');
        $disabled = in_array($switch, ['content', 'link', 'media']);

        // @todo recheck.
        // Disable media player for Twitter, Instagram, Pinterest, etc.
        // Some providers have dynamic and anti-mainstream iframe sizes.
        if ($disabled && Internals::irrational($provider)) {
          $settings['media_switch'] = '';
          $blazies->set('switch', '')
            ->set('is.player', FALSE)
            ->set('use.player', FALSE);
        }

        // Since 2.17, blazy is capable to lazy load HTML, like any media.
        // @todo make it usable for non-media contents here.
        if (!$unlazy && $media) {
          $content = $this->toHtml($build['content'], 'div', 'media__html');
          $content = $this->renderInIsolation($content);
          $content = preg_replace('/\s+/', ' ', $content->__toString());
          $content = base64_encode($content);

          $blazies->set('media.encoded.content', $content)
            ->set('media.encoded.uri', Internals::DATA_TEXT);

          // This allows theme_blazy() to process it as workable media elements.
          $build['content'] = [];
        }
        else {
          // Disable all lazy stuffs since we got a brick here.
          Internals::contently($settings);
        }
      }
    }
  }

  /**
   * Build out (Responsive) image.
   *
   * Since 2.9, many were moved into BlazyTheme to support custom work better.
   *
   * @todo remove all these after moving item_attributes to image.attributes.
   */
  private function buildMedia(array &$element, array &$build): void {
    $item  = $build['#item'];
    $attrs = $this->toHashtag($build, 'item_attributes');

    // Extract field item attributes for the theme function, and unset them
    // from the $item so that the field template does not re-render them.
    // (Responsive) image with item attributes, might be RDF.
    if ($item && isset($item->_attributes)) {
      $attrs += $item->_attributes;
      unset($item->_attributes);
    }

    // Pass item_attributes to theme_blazy():
    // https://www.drupal.org/project/blazy/issues/3374519.
    $element['#item_attributes'] = Blazy::sanitize($attrs);
  }

  /**
   * Prepares Blazy settings.
   *
   * Supports galeries if provided, updates $settings.
   * Cases: Blazy within Views gallery, or references without direct image.
   * Views may flatten out the array, bail out.
   * What we do here is extract the formatter settings from the first found
   * image and pass its settings to this container so that Blazy Grid which
   * lacks of settings may know if it should load/ display a lightbox, etc.
   * Lightbox should work without `Use field template` checked.
   */
  private function getBlazySettings(array $build) {
    $settings = $this->toHashtag($build);
    $blazies  = $this->verifySafely($settings);

    if ($data = $blazies->get('first.data')) {
      if (is_array($data)) {
        $this->isBlazy($settings, $data);
      }
    }
    return $settings;
  }

  /**
   * Prepares the Blazy output as a structured array ready for ::renderer().
   *
   * @param array $element
   *   The renderable array being modified.
   * @param array $build
   *   The array of information containing the required Image or File item
   *   object, settings, optional container attributes. An arbitrary storage
   *   we can mess up before printing them into the $element.
   */
  private function prepareBlazy(array &$element, array $build) {
    $item       = $build['#item'];
    $settings   = &$build['#settings'];
    $blazies    = $settings['blazies'];
    $attributes = &$build['#attributes'];
    $captions   = Internals::toContent($build, TRUE, ['captions', 'caption']);
    $captions   = array_filter($captions);

    $blazies->set('is.captioned', count($captions) > 0);

    // Only add figure for grid if using Blazy Filter [caption] shortcode mixed
    // with core [data-caption]. The rest should just have figure tags, either
    // standalone images, or sliders.
    if ($blazies->is('figcaption') && $blazies->is('grid')) {
      $blazies->set('item.wrapper_tag', 'figure')
        ->set('item.wrapper_attributes.class', ['blazy__content']);
    }

    // Blazy has 3 attributes: attributes, item_attributes, url_attributes, yet
    // provides optional ones. No defaults are provided for all these.
    $theme_attributes = BlazyDefault::themeAttributes();
    foreach ($theme_attributes as $key) {
      $key = $key . '_attributes';
      $build["#$key"] = $this->themeAttributes($key, $blazies, $build);
    }

    // Initial feature checks, URI, delta, media features, etc.
    // @todo remove this before 3.x release.
    $item_attributes = &$build['#item_attributes'];

    // Ensures CheckItem::essentials() called once.
    Internals::prepare($settings, $item, TRUE);

    // Build thumbnail and optional placeholder based on thumbnail.
    // Prepare image URL and its dimensions, including for rich-media content,
    // such as for local video poster image if a poster URI is provided.
    Internals::prepared($settings, $item);

    // Allows altering the settings for individual items.
    // Such as disabling lightbox for inline media player.
    $this->moduleHandler->alter('blazy_item', $settings, $attributes, $item_attributes);

    // Update media switcher based on the hook_blazy_item_alter.
    $blazies    = $settings['blazies'];
    $api_switch = $blazies->get('switch');
    if ($api_switch && $switch = $settings['media_switch'] ?? NULL) {
      if ($switch != $api_switch) {
        $settings['media_switch'] = $api_switch;
      }
    }

    // Only process (Responsive) image/ video if no rich-media are provided.
    // @todo recheck move it above before prepare if any needs or better.
    $build['content'] = Internals::toContent($build, TRUE);
    $this->buildContent($element, $build);
    if (empty($build['content'])) {
      $this->buildMedia($element, $build);
    }

    // Provides extra attributes as needed.
    // Was planned to replace sub-module item markups if similarity is found for
    // theme_gridstack_box(), theme_slick_slide(), etc. Likely for Blazy 3.x+.
    // Since 2.17, it is optional at Blazy UI under `Use theme_blazy()` option.
    $blazies = $settings['blazies'];
    foreach ($theme_attributes as $key) {
      $key   = $key . '_attributes';
      $attrs = $this->themeAttributes($key, $blazies, $build);

      // Sanitize potential user-defined attributes such as from BlazyFilter.
      $element["#$key"] = $attrs ? Blazy::sanitize($attrs) : [];
    }

    // Provides captions, if so configured.
    if ($captions) {
      $this->toCaption($element, $settings, $captions);
    }

    // Preparing Blazy to replace other blazy-related content/ item markups.
    // Composing or layering is crucial for mixed media (icon over CTA or text
    // or lightbox links or iframe over image or CSS background over noscript
    // which cannot be simply dumped as array without elaborate arrangements).
    $blazies = $settings['blazies'];
    foreach (BlazyDefault::themeContents() as $key => $default) {
      $defaults         = $this->toHashtag($build, $key, $default);
      $programs         = $blazies->get('html.' . $key, $default);
      $values           = $this->merge($programs, $defaults);
      $element["#$key"] = $this->merge($values, $element, "#$key");
    }

    // Fixed for media switch and lightboxes with Pinterest and Instagram API.
    $providers = array_keys(BlazyDefault::dyComponents());
    if ($provider = $blazies->get('media.provider')) {
      if (in_array($provider, $providers) && $blazies->is($provider)) {
        $element['#attached']['library'][] = 'blazy/' . $provider;
        $applicable = !$blazies->is('lightbox');

        // VEF does not need API initializer.
        if ($provider == 'instagram') {
          $applicable = $applicable && $blazies->use('instagram_api');
        }

        if ($applicable) {
          $attributes['class'][] = 'b-' . $provider;
        }
      }
    }

    // Provides all media cache.
    // See https://www.drupal.org/project/drupal/issues/2469277.
    if (!$blazies->is('cache_deferred')) {
      if ($caches = $blazies->get('cache.metadata', [])) {
        if (isset($caches['tags'])) {
          $caches['tags'] = array_unique($caches['tags'], SORT_REGULAR);
        }
        $element['#cache'] = $caches;
      }
    }

    // Pass common elements to theme_blazy().
    $element['#attributes'] = Blazy::sanitize($attributes);
    $element['#item']       = $build['#item'];
    $element['#settings']   = $settings;
  }

  /**
   * Returns available theme attributes to account for hook_alters.
   */
  private function themeAttributes($key, $blazies, array $build): array {
    $defaults = $this->toHashtag($build, $key);
    $programs = $blazies->get('item.' . $key, []);

    return $this->merge($programs, $defaults);
  }

  /**
   * Returns a theme_field() output.
   */
  private function themeField(array $data, array $settings): array {
    // Pass items as regular index children to theme_field().
    // Runs after settings.
    $build = $this->toElementChildren($data);

    // @nottodo refactor and move non-children out of here at 3.x.
    // We don't use #settings here to avoid conflicts with others because
    // theme_field() is not managed by blazy.
    $build['#blazy'] = $settings;
    $this->setAttachments($build, $settings);
    return $build;
  }

  /**
   * Returns a theme_item_list() output.
   */
  private function themeItemList(array $data, array $settings, $blazies): array {
    // Take over theme_field() with a theme_item_list(), if so configured.
    // The reason: this is not only fed by field items, but also Views rows.
    $data['#settings'] = $settings;
    $content = [
      '#build'      => $data,
      '#pre_render' => [[$this, 'preRenderBuild']],
    ];

    // Yet allows theme_field(), if so required, such as for linked_field.
    return $blazies->use('theme_field') ? [$content] : $content;
  }

  /**
   * Provides captions, if any.
   */
  private function toCaption(array &$element, array &$settings, array $captions): void {
    $blazies = $settings['blazies'];
    $id      = $blazies->get('item.id', 'blazy');
    $id      = $id == 'content' ? 'blazy' : $id;
    $self    = $id == 'blazy';
    $prefix  = $self ? $id . '__caption--' : $id . '__';
    $context = ['prefix' => $prefix, 'id' => $id];

    if ($output = $this->buildCaption($captions, $blazies, $prefix, $id)) {
      $element['#captions'] = $output;

      // @todo remove debug:
      // if (!$self) {
      // $element['#caption_attributes']['class'][] = 'blazy__caption';
      // }
      $element['#caption_attributes']['class'][] = $id . '__caption';

      // Overlays are media players/ nested sliders over images seen at Slick/
      // Splide Paragraphs and their Views styles, only treated as a caption.
      // Established since Slick:7.2. as the first client requirements.
      if (!empty($output['overlay'])) {
        // A wrapper for descriptions, titles, etc. when overlay exists
        // so they can be grouped, split, moved, overlayed over overlay, etc.
        // Perhaps ID__caption--content is better, but leave the ancient alone.
        $element['#caption_content_attributes']['class'][] = $prefix . 'data';
      }

      // Allows altering the captions to minimize Twig works for minor needs.
      $this->moduleHandler->alter('blazy_caption', $element, $settings, $context);
    }
  }

  /**
   * Prepares Blazy outputs, extract items as indices.
   *
   * If children are grouped within items property, reset to indexed keys.
   * Blazy comes late to the party after sub-modules decided what they want
   * where items may be stored as direct indices, or put into items property.
   * Actually the same issue happens at core where contents may be indexed or
   * grouped. Meaning not a problem at all, only a problem for consistency.
   */
  private function toElementChildren(array $build): array {
    $build = $build['items']
      ?? array_filter($build, fn($k) => is_int($k), ARRAY_FILTER_USE_KEY);

    unset(
      $build['#entity'],
      $build['#settings'],
      $build['items'],
      $build['settings']
    );

    return $build;
  }

  /**
   * Provides linkable content.
   */
  private function toLink(array &$element, $blazies): void {
    $url = $blazies->get('media.link') ?: $blazies->get('entity.url');
    $switch = $blazies->get('switch');
    // @todo enable $delta = $blazies->get('delta');
    if ($switch == 'link' && $urls = $blazies->get('field.values.link', [])) {
      $url = reset($urls);
      // @todo add option to map links to images.
      // if (isset($urls[$delta])) {
      // $url = $urls[$delta];
      // }
    }

    if ($url) {
      // If formatted link with title and value, extract its URL only.
      if (is_array($url) && isset($url['#url'])) {
        $url = $url['#url'];
      }

      if ($url instanceof Url) {
        $url = $url->toString();
      }

      // Plain text field, link field w/o plain text URL, core linked Image.
      if ($url) {
        $element['#url'] = $url;
        $element['#url_attributes']['class'][] = 'b-link';

        if ($blazies->is('bg')) {
          $element['#url_attributes']['class'][] = 'b-link--bg';
        }
      }
    }
  }

}
