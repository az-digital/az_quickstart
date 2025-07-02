<?php

namespace Drupal\blazy\Views;

use Drupal\Component\Utility\Html;
use Drupal\views\Views;

/**
 * A base for blazy views integration to support fieldable entities.
 *
 * @see \Drupal\mason\Plugin\views\style\MasonViews
 * @see \Drupal\gridstack\Plugin\views\style\GridStackViews
 * @see \Drupal\slick_views\Plugin\views\style\SlickViews
 * @see \Drupal\splide\Plugin\views\style\SplideViews
 */
abstract class BlazyStylePluginBase extends BlazyStyleBase implements BlazyStylePluginInterface {

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesGrouping = FALSE;

  /**
   * The Views as options.
   *
   * @var array
   */
  protected $viewsOptions;

  /**
   * {@inheritdoc}
   */
  protected function buildElement(array &$element, $row, $delta) {
    $this->manager->hashtag($element);

    $settings = &$element['#settings'];
    $blazies  = $this->reset($settings);
    $_image   = $settings['image'] ?? NULL;

    $blazies->set('delta', $delta);
    $captions = $this->getCaption($delta, $settings, $row);

    if ($extras = $element[static::$captionId] ?? []) {
      $captions = array_merge($captions, $extras);
      unset($element[static::$captionId]);
    }

    // Add layout field, may be a list field, or builtin layout options.
    if (!empty($settings['layout'])) {
      $this->getLayout($settings, $delta);
    }

    // Add main image fields if so configured.
    // Supports individual grid/box image style either inline IMG, or CSS.
    $element['#delta'] = $delta;
    if ($_image || $captions) {
      $image = $this->getImageRenderable($settings, $row, $delta);
      $rendered = $image['rendered'] ?? [];
      $element['#item'] = $image['raw'] ?? NULL;

      if ($rendered) {
        if (!empty($image['applicable'])) {
          if ($content = $rendered['#build']['content'] ?? []) {
            // Fixed for missing data-thumb thumbnail with local video, needed
            // by option static grid/ hoverable thumbnail.
            if ($blazies->get('thumbnail.uri') && $blazies->is('local_media')) {
              $blazies->set('is.multicontent', TRUE);
            }

            $element['content'] = $content;
          }
        }
        else {
          // VEF can be iframed as long as having URI, even from a thumbnail.
          if (!$this->mediaManager->iframeable($rendered, $settings)) {
            $element['content'][] = $rendered;
          }
        }
      }

      // Provides the relevant elements based on the configuration.
      // @todo refine for other formatters here.
      $this->toElement($blazies, $element, $captions);
    }
  }

  /**
   * Returns the caption elements.
   */
  protected function getCaption($index, array $settings, $row = NULL): array {
    $view     = $this->view;
    $captions = [];
    $keys     = array_keys($view->field);
    $keys     = array_combine($keys, $keys);
    $_link    = $settings['link'] ?? NULL;
    $_title   = $settings['title'] ?? NULL;
    $_overlay = $settings['overlay'] ?? NULL;
    $_caption = $settings['caption'] ?? [];

    // Caption items: link, title, overlay, and data, anything else selected.
    $captions['title']   = $this->getFieldRendered($index, $_title, TRUE, $row);
    $captions['link']    = $this->getFieldRendered($index, $_link, TRUE, $row);
    $captions['overlay'] = $this->getFieldRendered($index, $_overlay);

    // Exclude non-caption fields so that theme_views_view_fields() kicks in
    // and only render expected caption fields. As long as not-hidden, each
    // caption field should be wrapped with Views markups.
    if ($_caption) {
      $excludes = array_diff_assoc($keys, $_caption);
      foreach ($excludes as $field) {
        $view->field[$field]->options['exclude'] = TRUE;
      }

      if ($output = $view->rowPlugin->render($view->result[$index])) {
        $captions['data'][$index] = $output;
      }
    }

    return array_filter($captions);
  }

  /**
   * Returns the rendered layout fields, normally just string.
   */
  protected function getLayout(array &$settings, $index): void {
    $layout = $settings['layout'] ?? '';
    // Replacing useless field_NAME with its useful value.
    if (strpos($layout, 'field_') !== FALSE) {
      if ($value = $this->getField($index, $layout)) {
        $settings['layout'] = strip_tags($value);
      }
    }
  }

  /**
   * Returns the relevant elements based on the configuration.
   *
   * @todo remove for BlazyElementTrait if similar to field formatters.
   */
  protected function toElement($blazies, array &$data, array $captions): void {
    $delta    = $data['#delta'] ?? 0;
    $captions = array_filter($captions);

    // Call manager not formatter due to sub-module deviations.
    $this->manager->verifyItem($data, $delta);

    // Provides inline SVG if applicable.
    // @todo recheck $this->viewSvg($data);
    $this->themeBlazy($data, $captions, $delta);
  }

  /**
   * Returns available fields for select options.
   */
  protected function getDefinedFieldOptions(array $defined_options = []): array {
    $field_names = $this->displayHandler->getFieldLabels();
    $definition = [];
    $stages = [
      'blazy_media',
      'block_field',
      'colorbox',
      'entity_reference_entity_view',
      'gridstack_file',
      'gridstack_media',
      'video_embed_field_video',
      'youtube_video',
    ];

    // Formatter based fields.
    $options = [];
    foreach ($this->displayHandler->getOption('fields') as $field => $handler) {
      // This is formatter based type, not actual field type.
      if ($formatter = ($handler['type'] ?? NULL)) {
        switch ($formatter) {
          // @todo recheck other reasonable image-related formatters.
          case 'blazy':
          case 'image':
          case 'media':
          case 'media_thumbnail':
          case 'intense':
          case 'responsive_image':
          case 'svg_image_field_formatter':
          case 'video_embed_field_thumbnail':
          case 'video_embed_field_colorbox':
          case 'youtube_thumbnail':
            $options['images'][$field] = $field_names[$field];
            $options['overlays'][$field] = $field_names[$field];
            $options['thumbnails'][$field] = $field_names[$field];
            break;

          case 'list_key':
            $options['layouts'][$field] = $field_names[$field];
            break;

          case 'entity_reference_label':
          case 'text':
          case 'string':
          case 'link':
            $options['links'][$field] = $field_names[$field];
            $options['titles'][$field] = $field_names[$field];
            if ($formatter != 'link') {
              $options['thumb_captions'][$field] = $field_names[$field];
            }
            break;
        }

        $classes = ['list_key', 'entity_reference_label', 'text', 'string'];
        if (in_array($formatter, $classes)) {
          $options['classes'][$field] = $field_names[$field];
        }

        // Allows nested sliders.
        $sliders = strpos($formatter, 'slick') !== FALSE
          || strpos($formatter, 'splide') !== FALSE;
        if ($sliders || in_array($formatter, $stages)) {
          $options['overlays'][$field] = $field_names[$field];
        }

        // Allows advanced formatters/video as the main image replacement.
        // They are not reasonable for thumbnails, but main images.
        // Note: Certain Responsive image has no ID at Views, possibly a bug.
        if (in_array($formatter, $stages)) {
          $options['images'][$field] = $field_names[$field];
        }
      }

      // Content: title is not really a field, unless title.module installed.
      if (isset($handler['field'])) {
        if ($handler['field'] == 'title') {
          $options['classes'][$field] = $field_names[$field];
          $options['titles'][$field] = $field_names[$field];
          $options['thumb_captions'][$field] = $field_names[$field];
        }

        if ($handler['field'] == 'rendered_entity') {
          $options['images'][$field] = $field_names[$field];
          $options['overlays'][$field] = $field_names[$field];
        }

        if (in_array($handler['field'], ['nid', 'nothing', 'view_node'])) {
          $options['links'][$field] = $field_names[$field];
          $options['titles'][$field] = $field_names[$field];
        }

        if (in_array($handler['field'], ['created'])) {
          $options['classes'][$field] = $field_names[$field];
        }

        $blazies = strpos($handler['field'], 'blazy_') !== FALSE;
        if ($blazies) {
          $options['images'][$field] = $field_names[$field];
          $options['overlays'][$field] = $field_names[$field];
          $options['thumbnails'][$field] = $field_names[$field];
        }
      }

      // Captions can be anything to get custom works going.
      $options['captions'][$field] = $field_names[$field];
    }

    $definition['plugin_id'] = $this->getPluginId();
    $definition['settings'] = $this->options;
    $definition['_views'] = TRUE;

    // Provides the requested fields based on available $options.
    foreach ($defined_options as $key) {
      $definition[$key] = $options[$key] ?? [];
    }

    $contexts = [
      'handler' => $this->displayHandler,
      'view' => $this->view,
    ];
    $this->manager->moduleHandler()->alter('blazy_views_field_options', $definition, $contexts);

    return $definition;
  }

  /**
   * Returns an array of views for option list.
   *
   * Cannot use Views::getViewsAsOptions() as we need to limit to something.
   */
  protected function getViewsAsOptions($plugin = 'html_list'): array {
    if (!isset($this->viewsOptions[$plugin])) {
      $options = [];

      // Convert list of objects to options for the form.
      foreach (Views::getEnabledViews() as $name => $view) {
        foreach ($view->get('display') as $id => $display) {
          $valid = ($display['display_options']['style']['type'] ?? NULL) == $plugin;
          if ($valid) {
            $label = $view->label() . ' (' . $display['display_title'] . ')';
            $options[$name . ':' . $id] = Html::escape($label);
          }
        }
      }
      $this->viewsOptions[$plugin] = $options;
    }
    return $this->viewsOptions[$plugin];
  }

  /**
   * Builds the item using theme_blazy(), if so-configured.
   *
   * @todo remove for BlazyElementTrait if similar to field formatters.
   */
  private function themeBlazy(array &$element, array $captions, $delta): void {
    $internal = $element;

    // Allows sub-modules to use theme_blazy() as their theme_ITEM() contents.
    if ($texts = $this->toBlazy($internal, $captions, $delta)) {
      $internal['captions'] = $texts;
    }

    if ($blazy = $this->formatter->getBlazy($internal)) {
      $output = $this->withHashtag($element, $blazy);
      $element[static::$itemId] = $output;
      $this->formatter->postBlazy($element, $output);
    }
    unset($element['content']);
  }

  /**
   * Provides relevant attributes to feed into theme_blazy().
   *
   * @todo remove for BlazyElementTrait if similar to field formatters.
   */
  private function toBlazy(array &$data, array &$captions, $delta): array {
    // Call manager not formatter due to sub-module deviations.
    $this->manager->toBlazy($data, $captions, $delta);
    return $captions;
  }

}
