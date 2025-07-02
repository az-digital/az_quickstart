<?php

namespace Drupal\slick_views\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;

/**
 * Slick style plugin with grouping support.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "slick_grouping",
 *   title = @Translation("Slick Grouping"),
 *   help = @Translation("Display the results in a Slick carousel with grouping."),
 *   theme = "slick_wrapper",
 *   register_theme = FALSE,
 *   display_types = {"normal"}
 * )
 */
class SlickGrouping extends SlickViewsBase {

  /**
   * {@inheritdoc}
   */
  protected $usesGrouping = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesFields = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    foreach (['limit', 'optionset'] as $key) {
      $options['grouping_' . $key] = ['default' => ''];
    }

    return $options;
  }

  /**
   * Overrides parent::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $definition = $this->getDefinedFormScopes();

    $states = [
      'visible' => [
        'select[name*="[grouping][0][field]"]' => ['!value' => ''],
      ],
    ];

    if (!isset($form['grouping_limit'])) {
      $form['grouping_limit'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Grouping limit'),
        '#default_value' => $this->options['grouping_limit'],
        '#description' => $this->t('Limit the amount of rows per group. Leave it empty, or 0, for no limit. Applicable only to the first level. Be sure having enough rows.'),
        '#enforced' => TRUE,
        '#states' => $states,
      ];
    }

    if (!isset($form['grouping_optionset'])) {
      $form['grouping_optionset'] = [
        '#type' => 'select',
        '#title' => $this->t('Grouping optionset'),
        '#options' => $this->admin()->getOptionsetsByGroupOptions('main'),
        '#default_value' => $this->options['grouping_optionset'],
        '#description' => $this->t('If provided, the grouping header will be treated as Slick tabs and acts like simple filters. Else regular stacking slicks. Requires: Optionset thumbnail, Vanilla unchecked, and Randomize option disabled for all optionsets, else impressing broken grouping due to reordered slides. Combine with grids to have a complete insanity.'),
        '#enforced' => TRUE,
        '#states' => $states,
      ];
    }

    foreach (array_keys($form) as $key) {
      if (strpos($key, '#') !== FALSE) {
        continue;
      }

      if ($type = $form[$key]['#type'] ?? '') {
        if ($type != 'hidden') {
          $form[$key]['#wrapper_attributes']['class'][] = 'grid';
          $form[$key]['#wrapper_attributes']['data-b-w'] = 6;
          $form[$key]['#wrapper_attributes']['data-b-h'] = 1;
        }
      }
    }

    $groupings = $this->options['grouping'] ?: [];

    for ($i = 0; $i <= count($groupings); $i++) {
      foreach (['rendered', 'rendered_strip'] as $key) {
        // $form['grouping'][$i][$key]['#field_suffix'] = '&nbsp;';
        $form['grouping'][$i][$key]['#title_display'] = 'before';
      }

      foreach (['field', 'rendered', 'rendered_strip'] as $key) {
        if (isset($form['grouping'][$i][$key])) {
          $form['grouping'][$i][$key]['#wrapper_attributes']['class'][] = 'grid';
          $form['grouping'][$i][$key]['#wrapper_attributes']['data-b-w'] = 6;
          $form['grouping'][$i][$key]['#wrapper_attributes']['data-b-h'] = 1;
        }
      }
    }

    $this->buildSettingsForm($form, $definition);

    if (isset($form['optionset_thumbnail'])) {
      $form['optionset_thumbnail']['#description'] .= ' ' . $this->t('This will be used (taken over) for grouping tabs if Grouping optionset is provided. Including all thumbnail-related options: Skin tthumbnail, Thumbnail position.');
    }
  }

  /**
   * Overrides StylePluginBase::render().
   */
  public function render() {
    $sets     = parent::render();
    $settings = $this->options;
    $grouping = empty($settings['grouping']) ? [] : array_filter($settings['grouping']);
    $tabs     = !empty($settings['grouping_optionset']) && !empty($settings['optionset_thumbnail']);
    $tags     = ['span', 'a', 'em', 'strong', 'i', 'button'];

    if (!empty($grouping) && $tabs) {
      $options = [];
      foreach ($sets as $set) {
        $thumb = [];
        $options['nav'] = TRUE;
        $options['skin'] = '';
        $options['skin_thumbnail'] = $settings['skin_thumbnail'];
        $options['thumbnail_position'] = $settings['thumbnail_position'];
        $options['optionset'] = $settings['grouping_optionset'];
        $options['optionset_thumbnail'] = $settings['optionset_thumbnail'];

        $slide = [
          '#settings' => $options,
          static::$itemId => $set,
        ];

        $thumb[static::$itemId]['#markup'] = empty($set['#title']) ? '' : strip_tags($set['#title'], '<span><a><em><strong><i><button>');
        $thumb[static::$itemId]['#allowed_tags'] = $tags;

        $build['items'][] = $slide;
        $build[static::$navId]['items'][] = $thumb;
      }

      $build['#settings'] = $options;
      $sets = $this->manager->build($build);
    }

    return $sets;
  }

  /**
   * Overrides StylePluginBase::renderRowGroup().
   */
  protected function renderRowGroup(array $rows = [], $level = 0) {
    $view      = $this->view;
    $settings  = $this->options;
    $view_name = $view->storage->id();
    $view_mode = $view->current_display;
    $plugin_id = $this->getPluginId();
    $grouping  = empty($settings['grouping']) ? [] : array_filter($settings['grouping']);
    $id        = $grouping ? "{$view_name}-{$view_mode}-{$level}" : "{$view_name}-{$view_mode}";
    $id        = $this->manager->getHtmlId($plugin_id . '-views-' . $id, $settings['id'] ?? '');
    $settings  = $this->buildSettings();

    // Prepare needed settings to work with.
    $settings['id'] = $id;
    if (empty($grouping) && empty($settings['grouping_optionset'])) {
      $settings['nav'] = !$settings['vanilla'] && $settings['optionset_thumbnail'] && isset($view->result[1]);
    }

    $build = $this->buildElements($settings, $rows);

    // Extracts Blazy formatter settings if available.
    $this->checkBlazy($settings, $build, $rows);

    $build['#settings'] = $settings;

    return $this->manager->build($build);
  }

  /**
   * Overrides StylePluginBase::renderGroupingSets().
   *
   * @see https://www.drupal.org/node/2639300
   */
  public function renderGroupingSets($sets) {
    $output = [];
    $grouping = empty($this->options['grouping'])
      ? [] : array_filter($this->options['grouping']);

    foreach ($sets as $set) {
      $level = $set['level'] ?? 0;
      $row   = reset($set['rows']);

      // Render as a grouping set.
      if (is_array($row) && isset($row['group'])) {
        $single_output = [
          '#theme' => $this->view->buildThemeFunctions($this->groupingTheme),
          '#view' => $this->view,
          '#grouping' => $grouping[$level],
          '#rows' => $set['rows'],
        ];
      }
      // Render as a record set.
      else {
        $slick = $this->renderRowGroup($set['rows'], $level);

        // Views leaves the first grouping header to the style plugin.
        if (!empty($grouping) && $level == 0) {
          if (empty($this->options['grouping_optionset'])) {
            $content[0] = $slick;
            $content[0]['#prefix'] = '<h2 class="view-grouping-header">' . $set['group'] . '</h2>';

            $single_output = $content;
            $single_output['#theme_wrappers'][] = 'container';
            $single_output['#attributes']['class'][] = 'view-grouping';
          }
          else {
            $single_output = $slick;
          }
        }
        else {
          $single_output = $slick;
        }
      }

      $single_output['#grouping_level'] = $level;
      $single_output['#title'] = $set['group'];

      $output[] = $single_output;
    }

    return $output;
  }

  /**
   * Overrides StylePluginBase::renderGrouping().
   */
  public function renderGrouping($records, $groupings = [], $group_rendered = NULL) {
    $sets = parent::renderGrouping($records, $groupings, $group_rendered);
    $grouping = empty($groupings) ? [] : array_filter($groupings);

    // Only add limits for the first top level grouping to avoid recursiveness.
    if (!empty($grouping) && !empty($this->options['grouping_limit'])) {
      $new_sets = array_values($sets);
      $sets = [];

      foreach ($new_sets as $set) {
        $set['rows'] = array_slice($set['rows'], 0, $this->options['grouping_limit'], TRUE);
        $sets[] = $set;
      }
    }

    return $sets;
  }

}
