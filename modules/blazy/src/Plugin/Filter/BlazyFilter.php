<?php

namespace Drupal\blazy\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\blazy\Media\BlazyFile;
use Drupal\filter\FilterProcessResult;

/**
 * Provides a filter to lazyload image, or iframe elements.
 *
 * Best after Align images, caption images.
 *
 * @Filter(
 *   id = "blazy_filter",
 *   title = @Translation("Blazy"),
 *   description = @Translation("Lazyload inline images, or video iframes using Blazy."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   settings = {
 *     "filter_tags" = {"img" = "img", "iframe" = "iframe"},
 *     "media_switch" = "",
 *     "box_style" = "",
 *     "box_media_style" = "",
 *     "hybrid_style" = "",
 *     "use_data_uri" = "0",
 *   },
 *   weight = 3
 * )
 */
class BlazyFilter extends BlazyFilterBase {

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
  protected static $captionId = 'captions';

  /**
   * {@inheritdoc}
   */
  protected static $shortcode = 'item';

  /**
   * {@inheritdoc}
   *
   * @todo to support preload, split into two phases: collect uris and render.
   */
  public function process($text, $langcode) {
    $this->result = $result = new FilterProcessResult($text);
    $this->langcode = $langcode;

    if (empty($text)) {
      return $result;
    }

    // Prepare settings.
    $settings = $this->buildSettings($text);
    $blazies  = $settings['blazies'];

    // Checks if any shortcodes.
    if (stristr($text, '[' . static::$namespace) !== FALSE) {
      $text = $this->shortcode($text, static::$namespace, static::$shortcode);
      // Shortcode cannot co-exist with deprecated grid.
      $blazies->set('is.deprecated_grid', FALSE);
    }

    // Load text as \DOMDocument to work with.
    $dom = Html::load($text);

    // Process individual images and or iframes.
    $processed = FALSE;
    if ($this->processDom($dom, $settings)) {
      $processed = TRUE;
    }

    // Process shortcode grids and entities, not always images or iframes.
    if ($this->processShortcode($dom, $settings)) {
      $processed = TRUE;
    }

    // If we have relevant processed texts.
    if ($processed) {
      // Cleans up invalid, or moved nodes.
      $this->cleanupNodes($dom);

      // Attach relevant libraries.
      $attach = $this->attach($settings);
      $attachments = $this->manager->attach($attach);
      $result->addAttachments($attachments);
    }

    // Sets processed texts.
    $result->setProcessedText(Html::serialize($dom));

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      $text = file_get_contents(dirname(__FILE__) . "/FILTER_TIPS.md");
      return $this->manager->markdown($text);
    }
    else {
      return $this->t('<b>Blazy</b>: <ul><li>With HTML: <code>[blazy]..[item]IMG[/item]..[/blazy]</code></li><li>With self-closing using data entity, <code>data=ENTITY_TYPE:ID:FIELD_NAME:FIELD_IMAGE</code>:<br><code>[blazy data="node:44:field_media" /]</code>. <code>FIELD_IMAGE</code> is optional for video poster, or hires, normally <code>field_media_image</code>.<li>Grid format:
      <code>STYLE:SMALL-MEDIUM-LARGE</code>, where <code>STYLE</code> is one of <code>column grid
      flex nativegrid</code>.<br>
      <code>[blazy grid="column:2-3-4" data="node:44:field_media" /]</code><br>
      <code>[blazy grid="nativegrid:2-3-4"]...[/blazy]</code><br>
      <code>[blazy grid="nativegrid:2-3-4x4 4x3 2x2 2x4 2x2 2x3 2x3 4x2 4x2"]...[/blazy]
      </code><br>Only nativegrid can have number or dimension string (4x4...). The rest number only.</li><li>The attributes grid, data, settings can be combined into one [blazy].</li><li>To disable, add <code>data-unblazy</code>, e.g.: <code>&lt;img data-unblazy</code> or <code>&lt;iframe data-unblazy</code>. Add width and height for SVG, and non-uploaded images without image styles.</li></ul>');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // @todo add more sensible form items.
    $form['filter_tags'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enable HTML tags'),
      '#options' => [
        'img' => $this->t('Image'),
        'iframe' => $this->t('Video iframe'),
      ],
      '#default_value' => empty($this->settings['filter_tags']) ? [] : array_values((array) $this->settings['filter_tags']),
      '#description' => $this->t('To disable Blazy per individual item, add attribute <code>data-unblazy</code>.'),
      '#prefix' => '<p>' . $this->t('<b>Warning!</b> Blazy Filter is useless and broken when you enable <b>Media embed</b> or <b>Display embedded entities</b>. You can disable Blazy Filter in favor of Blazy formatter embedded inside <b>Media embed</b> or <b>Display embedded entities</b> instead. However it might be useful for User Generated Contents (UGC) where Entity/Media Embed are likely more for privileged users, authors, editors, admins, alike. Or when Entity/Media Embed is disabled. Or when editors prefer pasting embed codes from video providers rather than creating media entities. Or want the new shortcodes for embedding known entity, grid, Native Grid, etc.') . '</p>',
    ];

    $this->mediaSwitchForm($form);

    $form['use_data_uri'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Trust data URI'),
      '#default_value' => $this->settings['use_data_uri'] ?? FALSE,
      '#description' => $this->t('Enable to support the use of data URI. Leave it unchecked if unsure, or never use data URI. <b>Warning! It has security implications given to untrusted users.</b>'),
      '#suffix' => '<p>' . $this->t('Recommended placement after Align / Caption images. Not tested against, nor dependent on, Shortcode module. Be sure to place Blazy filter before any other Shortcode if installed.') . '</p>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildImageItem(array &$build, &$node, $delta = 0): void {
    parent::buildImageItem($build, $node, $delta);

    $settings = $build['#settings'];
    $blazies  = $settings['blazies'];

    // @todo remove deprecated too-catch-all grid for shortcode at 3.x+.
    if ($blazies->is('grid') || $blazies->no('item_container')) {
      return;
    }

    // Responsive image with aspect ratio requires an extra container to work
    // with Align/ Caption images filters.
    $classes = [
      'media-wrapper',
      'media-wrapper--blazy',
    ];

    // Copy all attributes of the original node to the item_attributes.
    if ($attrs = $blazies->get('item.raw_attributes', [])) {
      foreach ($attrs as $name => $value) {
        if ($name == 'src' || !$value) {
          continue;
        }

        // Add classes for alignment.
        // Move classes (align-BLAH,etc) to Blazy container, not image so to
        // work with alignments and aspect ratio.
        if (is_string($value)) {
          if ($name == 'align' || $name == 'style') {
            if (strpos($value, 'left') !== FALSE) {
              $classes[] = 'alignment-left';
            }
            elseif (strpos($value, 'right') !== FALSE) {
              $classes[] = 'alignment-right';
            }
          }
        }
      }
    }

    if ($type = $blazies->get('media.type')) {
      $classes[] = 'media-wrapper--' . str_replace('_', '-', $type);
    }

    $build['#media_attributes']['class'] = array_unique($classes);
  }

  /**
   * {@inheritdoc}
   */
  protected function init(array &$settings, $text): void {
    // @todo remove at 3.x or so.
    $this->deprecatedGridSettings($settings, $text);

    parent::init($settings, $text);
  }

  /**
   * {@inheritdoc}
   */
  protected function postSettings(array &$settings): void {
    $blazies = $settings['blazies'];
    if ($style = ($settings['hybrid_style'] ?? NULL)) {
      // @todo move it out of here due to requiring URI to determine style.
      if ($blazies->is('resimage')) {
        try {
          if ($resimage = $this->manager->load($style, 'responsive_image_style')) {
            $settings['responsive_image_style'] = $style;
            $blazies->set('resimage.style', $resimage);
          }
        }
        catch (\Exception $ignore) {
          // Likely SVG, etc. without dimensions.
        }
      }

      if (empty($settings['responsive_image_style'])) {
        $settings['image_style'] = $style;
      }
    }

    parent::postSettings($settings);
  }

  /**
   * Build the blazy, the node might be grid, or direct img/ iframe.
   */
  private function build(\DOMElement $node, array &$settings, $delta = 0): array {
    $blazies = $settings['blazies'];

    if ($node->tagName == static::$namespace) {
      $dataset = $node->getAttribute('data');

      $blazies->set('is.shortcode', TRUE);

      // Extract settings from attributes.
      $blazies->set('was.initialized', FALSE);
      $this->extractSettings($node, $settings);

      if (!empty($dataset) && mb_strpos($dataset, ":") !== FALSE) {
        $dataset = strip_tags($dataset);
        $node->setAttribute('data', '');
        return $this->withEntityShortcode($settings, $dataset);
      }

      return $this->withDomShortcode($node, $settings);
    }

    $build = ['#settings' => $settings, '#item' => NULL];
    return $this->withDomElement($build, $node, $delta);
  }

  /**
   * Process grids and entities, not always images or iframes.
   */
  private function processDom(\DOMDocument $dom, array $settings): bool {
    $processed  = FALSE;
    $blazies    = $settings['blazies'];
    $tags       = array_values((array) $this->settings['filter_tags']);
    $grid_items = $grid_nodes = [];

    if (!empty($tags)) {
      $nodes = $this->validNodes($dom, $tags, 'data-unblazy');
      if (count($nodes) > 0) {
        $processed = TRUE;
        foreach ($nodes as $delta => $node) {
          $sets  = $settings;
          $blazy = $sets['blazies']->reset($sets);

          $blazy->set('delta', $delta);

          if ($output = $this->build($node, $sets, $delta)) {

            // @todo remove deprecated too-catch-all post Blazy 3.x.
            if ($blazy->is('deprecated_grid')) {
              $grid_items[] = $output;
              $grid_nodes[] = $node;
            }
            else {
              $this->render($node, $output);
            }
          }
        }

        // Builds the grids if so provided via [data-column], or [data-grid].
        // @todo deprecated for grid shortcode.
        if ($blazies->is('deprecated_grid')) {
          $this->buildDeprecatedGrid($settings, $grid_nodes, $grid_items);
        }
      }
    }
    return $processed;
  }

  /**
   * Process shortcode grids and entities, not always images or iframes.
   */
  private function processShortcode(\DOMDocument $dom, array $settings): bool {
    $processed = FALSE;
    $nodes = $this->validNodes($dom, [static::$namespace]);

    if (count($nodes) > 0) {
      $processed = TRUE;
      foreach ($nodes as $delta => $node) {
        $sets  = $settings;
        $blazy = $sets['blazies']->reset($sets);

        $blazy->set('delta', $delta);

        if ($output = $this->build($node, $sets, $delta)) {
          $this->render($node, $output);
        }
      }
    }
    return $processed;
  }

  /**
   * Build the blazy using the DOM lookups.
   */
  private function withDomShortcode(\DOMElement $object, array &$settings): array {
    $text = $this->getHtml($object);
    if (empty($text)) {
      return [];
    }

    $dom = Html::load($text);
    $nodes = $this->getNodes($dom, '//item');
    if ($nodes->length == 0) {
      return [];
    }

    $blazies = $settings['blazies'];
    $count = $nodes->length;
    $settings['count'] = $count;

    $blazies->set('count', $count);

    $build = ['#settings' => $settings];

    foreach ($nodes as $delta => $node) {
      if (!($node instanceof \DOMElement)) {
        continue;
      }

      $sets = $settings;
      $element = [
        '#delta' => $delta,
        '#attributes' => [],
        '#item' => NULL,
        '#settings' => $sets,
      ];

      $content = $this->withDomElement($element, $node, $delta)
        ?: ['#markup' => $dom->saveHtml($node)];

      $element['content'] = $content;
      unset($element['captions']);

      $build[$delta] = $element;
    }

    return $this->manager->build($build);
  }

  /**
   * Build the individual item.
   */
  private function withDomElement(array &$build, $node, $delta): array {
    $media    = NULL;
    $settings = &$build['#settings'];
    $tn_uri   = $node->getAttribute('data-b-thumb');

    // @todo remove for data-b-thumb at 3.x.
    if (!$tn_uri) {
      $tn_uri = $node->getAttribute('data-thumb');
    }
    $info = [
      'delta' => $delta,
      'thumbnail.uri' => $tn_uri,
    ];

    $this->manager->toSettings($settings, $info);
    $blazies = $settings['blazies'];

    // If using grid, node is grid item.
    if ($node->tagName == static::$shortcode) {
      $this->buildItemAttributes($build, $node, $delta);

      if ($text = $this->getHtml($node)) {
        $dom = Html::load($text);
        $items = $this->getNodes($dom, '//iframe | //img');

        if ($items->length > 0) {
          $media = $this->getValidNode($items);
        }
      }
    }
    // Else just img or iframe.
    else {
      $media = $node;
    }

    if ($media == NULL) {
      return [];
    }

    // Build item settings, image, and caption, including URI here.
    $this->buildItemContent($build, $media, $delta);

    // Marks invalid, unknown, missing IMG or IFRAME for removal.
    // Be sure to not affect external images, only strip missing local URI.
    $uri = $blazies->get('image.uri');

    $missing = FALSE;
    if ($uri && !BlazyFile::isExternal($uri)) {
      $missing = BlazyFile::isValidUri($uri) && !is_file($uri);
    }

    if (empty($uri) || $missing) {
      $media->setAttribute('class', 'blazy-removed');
      return [];
    }

    // Provides the relevant elements based on the configuration.
    return $this->toElement($blazies, $build);
  }

  /**
   * Build the blazy using the node ID and field_name.
   */
  private function withEntityShortcode(array &$settings, $attribute): array {
    $list = $this->formatterSettings($settings, $attribute);

    if (!$list) {
      return [];
    }

    $blazies = $settings['blazies'];
    $count = $blazies->get('count');

    if ($count > 0 && $type = $blazies->get('field.type')) {
      $formatter = NULL;
      $handler = $blazies->get('field.handler');

      if ($type == 'image') {
        $formatter = 'blazy';
      }
      elseif ($type == 'file') {
        $formatter = 'blazy_file';
      }
      // @todo refine for main stage, etc.
      elseif ($type == 'entity_reference' || $type == 'entity_reference_revisions') {
        if ($handler == 'default:media') {
          $formatter = 'blazy_media';
        }
        else {
          $formatter = 'blazy_entity';
        }
      }
      elseif ($blazies->is('string')) {
        $formatter = 'blazy_oembed';
      }
      elseif ($blazies->is('text')) {
        $formatter = 'blazy_text';
      }

      if ($formatter) {
        return $list->view([
          'type' => $formatter,
          'settings' => $settings,
        ]);
      }
    }

    return [];
  }

  /**
   * Cleanups invalid nodes or those of which their contents are moved.
   *
   * @param \DOMDocument $dom
   *   The HTML DOM object being modified.
   */
  private function cleanupNodes(\DOMDocument $dom): void {
    $xpath = new \DOMXPath($dom);
    $nodes = $xpath->query("//*[contains(@class, 'blazy-removed')]");
    if ($nodes->length > 0) {
      $this->removeNodes($nodes);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @todo deprecate and remove for shortcodes at Blazy 3.x.
   */
  protected function cleanupImageCaption(array &$build, &$node, &$item): void {
    $settings = $build['#settings'];
    $blazies = $settings['blazies'];

    if (!$blazies->is('shortcode')) {
      // Mark the FIGCAPTION for deletion because the caption moved into Blazy.
      $item->setAttribute('class', 'blazy-removed');

      // Marks figures for removal as its contents are moved into grids.
      // @todo remove deprecated too-catch-all grid for shortcode at 3.x+.
      if ($blazies->is('grid') && $node->parentNode) {
        $node->parentNode->setAttribute('class', 'blazy-removed');
      }
    }
  }

  /**
   * Build the grid.
   *
   * @param array $settings
   *   The settings array.
   * @param array $grid_nodes
   *   The grid nodes.
   * @param array $grid_items
   *   The renderable array of blazy item.
   *
   * @todo deprecate and remove for shortcodes at Blazy 4.x due to being
   * too catch-all, not selective like field formatters.
   */
  private function buildDeprecatedGrid(array &$settings, array $grid_nodes, array $grid_items = []): void {
    $blazies = $settings['blazies'];

    if (!$blazies->is('deprecated_grid') || empty($grid_items[0])) {
      return;
    }

    $build   = $grid_items[0]['#build'] ?? [];
    $subsets = $this->manager->toHashtag($build);
    $uri     = $subsets['uri'] ?? '';

    $blazies->set('first.uri', $uri);

    $first  = $grid_nodes[0];
    $dom    = $first->ownerDocument;
    $xpath  = new \DOMXPath($dom);
    $column = ($settings['style'] ?? '') == 'column';
    $query  = $column ? 'column' : 'grid';
    $grid   = NULL;

    // This is weird, variables not working for xpath?
    $nodes = $query == 'column' ? $xpath->query('//*[@data-column]') : $xpath->query('//*[@data-grid]');
    if ($nodes->length > 0 && $node = $nodes->item(0)) {
      if ($node instanceof \DOMElement) {
        $grid = $node->getAttribute('data-' . $query);
      }
    }

    if ($grid) {
      $grids = array_map('trim', explode(' ', $grid));

      foreach (['small', 'medium', 'large'] as $key => $item) {
        if (isset($grids[$key])) {
          $settings['grid_' . $item] = $grids[$key];
          $settings['grid'] = $grids[$key];
        }
      }

      $build = [
        'items' => $grid_items,
        '#settings' => $settings,
      ];

      $output = $this->manager->build($build);
      $altered_html = $this->manager->renderer()->render($output);

      // Checks if the IMG is managed by caption filter identified by figure.
      if ($first->parentNode && $first->parentNode->tagName == 'figure') {
        $first = $first->parentNode;
      }

      // Create the parent grid container, and put it before the first.
      // This extra container ensures hook_blazy_build_alter() aint screw up.
      $parent = $first->parentNode ? $first->parentNode : $first;

      $container = $parent->insertBefore($dom->createElement('div'), $first);
      $container->setAttribute('class', 'blazy-wrapper blazy-wrapper--filter');

      $updated_nodes = Html::load($altered_html)->getElementsByTagName('body')
        ->item(0)
        ->childNodes;

      foreach ($updated_nodes as $updated_node) {
        // Import the updated from the new DOMDocument into the original
        // one, importing also the child nodes of the updated node.
        $updated_node = $dom->importNode($updated_node, TRUE);
        $container->appendChild($updated_node);
      }

      // Cleanups old nodes already moved into grids.
      $this->removeNodes($grid_nodes);
    }
  }

  /**
   * Provides deprecated settings to be removed at 3.x or so.
   *
   * @todo remove deprecated too-catch-all grid for shortcode at 3.x+.
   */
  private function deprecatedGridSettings(array &$settings, $text = NULL): void {
    $blazies = $settings['blazies'];

    // The data-grid and data-column are deprecated for [blazy] shortcode.
    if ($text) {
      $grid = stristr($text, 'data-grid') !== FALSE;
      $column = stristr($text, 'data-column') !== FALSE;

      if ($column || $grid) {
        $settings['style'] = $column ? 'column' : 'grid';

        $blazies->set('is.grid', TRUE)
          ->set('is.deprecated_grid', TRUE);
      }
    }
  }

}
