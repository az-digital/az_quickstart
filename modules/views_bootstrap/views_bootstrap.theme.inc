<?php

/**
 * @file
 * Preprocessors and helper functions to make theming easier.
 */

use Drupal\Component\Utility\Html;
use Drupal\views_bootstrap\ViewsBootstrap;
use Drupal\Core\Template\Attribute;

/**
 * Prepares variables for views accordion templates.
 *
 * Default template: views-bootstrap-accordion.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_views_bootstrap_accordion(array &$vars) {
  $view = $vars['view'];
  $vars['id'] = ViewsBootstrap::getUniqueId($view);
  $panel_title_field = $view->style_plugin->options['panel_title_field'];
  $vars['attributes']['class'][] = 'panel-group';
  $vars['options'] = $view->style_plugin->options;
  if ($panel_title_field) {
    foreach ($vars['rows'] as $id => $row) {
      $vars['rows'][$id] = [];
      $vars['rows'][$id]['content'] = $row;
      $vars['rows'][$id]['title'] = $view->style_plugin->getField($id, $panel_title_field);
    }
  }
  else {
    // @TODO: This would be better as valdiation errors on the style plugin options form.
    \Drupal::messenger()->addWarning(t('@style style will not display without the "@field" setting.',
      [
        '@style' => $view->style_plugin->definition['title'],
        '@field' => 'Panel title',
      ]
    ));
  }
  // @TODO: Make sure that $vars['rows'] is rendered array.
  // @SEE: Have a look template_preprocess_views_view_unformatted()
  // and views-view-unformatted.html.twig
}

/**
 * Prepares variables for views cards templates.
 *
 * Default template: views-bootstrap-cards.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_views_bootstrap_cards(array &$vars) {
  $view = $vars['view'];
  $vars['id'] = ViewsBootstrap::getUniqueId($view);

  // Card rows.
  $image = $view->style_plugin->options['card_image_field'];
  $title = $view->style_plugin->options['card_title_field'];
  $content = $view->style_plugin->options['card_content_field'];

  foreach ($vars['rows'] as $id => $row) {
    $vars['rows'][$id] = [];
    $vars['rows'][$id]['image'] = $view->style_plugin->getField($id, $image);
    $vars['rows'][$id]['title'] = $view->style_plugin->getField($id, $title);
    $vars['rows'][$id]['content'] = $view->style_plugin->getField($id, $content);
  }
}

/**
 * Prepares variables for views carousel template.
 *
 * Default template: views-bootstrap-carousel.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_views_bootstrap_carousel(array &$vars) {
  $view = $vars['view'];
  $vars['id'] = ViewsBootstrap::getUniqueId($view);
  $vars['attributes']['class'][] = 'views-bootstrap-media-object';
  $vars['attributes']['class'][] = 'media-list';

  // Carousel options.
  $vars['interval'] = $view->style_plugin->options['interval'];
  $vars['navigation'] = $view->style_plugin->options['navigation'];
  $vars['indicators'] = $view->style_plugin->options['indicators'];
  $vars['pause'] = $view->style_plugin->options['pause'] ? 'hover' : FALSE;
  $vars['wrap'] = $view->style_plugin->options['wrap'];
  $vars['effect'] = $view->style_plugin->options['effect'];
  $vars['use_caption'] = $view->style_plugin->options['use_caption'];
  $vars['ride'] = $view->style_plugin->options['ride'];

  // Carousel rows.
  $image = $view->style_plugin->options['image'];
  $title = $view->style_plugin->options['title'];
  $description = $view->style_plugin->options['description'];
  $fieldLabels = $view->display_handler->getFieldLabels(TRUE);

  foreach ($vars['rows'] as $id => $row) {
    $vars['rows'][$id] = [];
    $vars['rows'][$id]['image'] = $view->style_plugin->getField($id, $image);
    $vars['rows'][$id]['title'] = $view->style_plugin->getField($id, $title);
    $vars['rows'][$id]['description'] = $view->style_plugin->getField($id, $description);
    // Add any additional fields to result.
    foreach (array_keys($fieldLabels) as $label) {
      if (!in_array($label, [$image, $title, $description])) {
        $vars['rows'][$id][$label] = $view->style_plugin->getField($id, $label);
      }
    }
  }

}

/**
 * Prepares variables for views grid templates.
 *
 * Default template: views-bootstrap-grid.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_views_bootstrap_grid(array &$vars) {
  $view = $vars['view'];
  $vars['id'] = ViewsBootstrap::getUniqueId($view);
  $vars['attributes'] = new Attribute(['class' => 'grid']);
  $options = $view->style_plugin->options;

  if ($options['grid_class']) {
    $grid_class = explode(' ', $options['grid_class']);
    $grid_classes = array_map([Html::class, 'cleanCssIdentifier'], array_filter($grid_class));
    $vars['attributes']->addClass($grid_classes);
  }

  $vars['row_attributes'] = new Attribute();
  foreach (ViewsBootstrap::getBreakpoints() as $breakpoint) {
    if ($options["col_$breakpoint"] == 'none') {
      continue;
    }
    $vars['row_attributes']->addClass($options["col_$breakpoint"]);
  }

  $vars['options'] = $options;
}

/**
 * Prepares variables for views list group templates.
 *
 * Default template: views-bootstrap-list-group.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_views_bootstrap_list_group(array &$vars) {
  $view = $vars['view'];
  $options = $view->style_plugin->options;
  $vars['id'] = ViewsBootstrap::getUniqueId($view);
  $vars['attributes']['class'][] = 'views-bootstrap-list-group';
  foreach ($vars['rows'] as $id => $row) {
    $vars['rows'][$id] = [];
    $vars['rows'][$id]['content'] = $row;
    $vars['rows'][$id]['title'] = $vars['view']->style_plugin->getField($id, $options['title_field']);
  }

}

/**
 * Prepares variables for views media object templates.
 *
 * Default template: views-bootstrap-media-object.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_views_bootstrap_media_object(array &$vars) {
  $vars['id'] = ViewsBootstrap::getUniqueId($vars['view']);
  $image_class = $vars['view']->style_plugin->options['image_class'];
  $image_field = $vars['view']->style_plugin->options['image_field'];
  $heading_field = $vars['view']->style_plugin->options['heading_field'];
  $body_field = $vars['view']->style_plugin->options['body_field'];

  foreach ($vars['rows'] as $id => $row) {
    $vars['rows'][$id] = [];
    $vars['classes'][$id] .= ' media-object';
    $vars['rows'][$id]['image_class'] = $image_class;
    $vars['rows'][$id]['image'] = $vars['view']->style_plugin->getField($id, $image_field);
    $vars['rows'][$id]['heading'] = $vars['view']->style_plugin->getField($id, $heading_field);
    $vars['rows'][$id]['body'] = $vars['view']->style_plugin->getField($id, $body_field);
  }
}

/**
 * Prepares variables for views tab templates.
 *
 * Default template: views-bootstrap-tab.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_views_bootstrap_tab(array &$vars) {
  $vars['id'] = ViewsBootstrap::getUniqueId($vars['view']);
  $view = $vars['view'];
  $tab_field = $view->style_plugin->options['tab_field'];
  $vars['tab_type'] = $view->style_plugin->options['tab_type'];
  $vars['justified'] = $view->style_plugin->options['justified'];

  // Get tabs.
  if ($tab_field) {
    if (isset($view->field[$tab_field])) {
      foreach (array_keys($vars['rows']) as $key) {
        $vars['tabs'][$key] = $view->style_plugin->getFieldValue($key, $tab_field);
      }
    }
    foreach ($vars['rows'] as $id => $row) {
      $vars['rows'][$id] = [];
      $vars['rows'][$id]['content'] = $row;
      $vars['rows'][$id]['attributes'] = new Attribute();
      if ($row_class = $view->style_plugin->getRowClass($id)) {
        $vars['rows'][$id]['attributes']->addClass($row_class);
      }
    }
  }
  else {
    // @TODO: This would be better as valdiation errors on the style plugin options form.
    \Drupal::messenger()->addWarning(t('@style style will not display without the "@field" setting.',
      [
        '@style' => $view->style_plugin->definition['title'],
        '@field' => 'Tab title',
      ]
    ));
  }
}

/**
 * Prepares variables for views table templates.
 *
 * Default template: views-bootstrap-table.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_views_bootstrap_table(array &$vars) {
  $vars['responsive'] = $vars['view']->style_plugin->options['responsive'];
  $vars['attributes']['class'][] = 'table';
  foreach (array_filter($vars['view']->style_plugin->options['bootstrap_styles']) as $style) {
    $vars['attributes']['class'][] = 'table-' . $style;
  }
}
