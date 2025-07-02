<?php

namespace Drupal\views_bootstrap\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\views_bootstrap\ViewsBootstrap;

/**
 * Style plugin to render each item in an ordered or unordered list.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "views_bootstrap_grid",
 *   title = @Translation("Bootstrap Grid"),
 *   help = @Translation("Displays rows in a Bootstrap Grid layout"),
 *   theme = "views_bootstrap_grid",
 *   theme_file = "../views_bootstrap.theme.inc",
 *   display_types = {"normal"}
 * )
 */
class ViewsBootstrapGrid extends StylePluginBase {
  /**
   * Overrides \Drupal\views\Plugin\views\style\StylePluginBase::usesRowPlugin.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Overrides \Drupal\views\Plugin\views\style\StylePluginBase::usesRowClass.
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;

  /**
   * Definition.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['grid_class'] = ['default' => ''];
    foreach (ViewsBootstrap::getBreakpoints() as $breakpoint) {
      $breakpoint_option = "col_$breakpoint";
      $options[$breakpoint_option] = ['default' => 'none'];
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['grid_class'] = [
      '#title' => $this->t('Grid row custom class'),
      '#description' => $this->t('Additional classes to provide on the grid row. Separated by a space.'),
      '#type' => 'textfield',
      '#size' => '30',
      '#default_value' => $this->options['grid_class'],
      '#weight' => 1,
    ];

    $form['row_class']['#title'] = $this->t('Custom column class');
    $form['row_class']['#weight'] = 2;

    foreach (ViewsBootstrap::getBreakpoints() as $breakpoint) {
      $breakpoint_option = "col_$breakpoint";
      $prefix = 'col' . ($breakpoint != 'xs' ? '-' . $breakpoint : '');
      $form[$breakpoint_option] = [
        '#type' => 'select',
        '#title' => $this->t("Column width of items at @breakpoint breakpoint", ['@breakpoint' => $breakpoint]),
        '#default_value' => $this->options[$breakpoint_option] ?? NULL,
        '#description' => $this->t("Set the number of columns each item should take up at the @breakpoint breakpoint and higher.", ['@breakpoint' => $breakpoint]),
        '#options' => [
          'none' => $this->t('None (or inherit from previous)'),
          $prefix => $this->t('Equal'),
          $prefix . '-auto' => $this->t('Fit to content'),
        ],
      ];
      foreach ([1, 2, 3, 4, 6, 12] as $width) {
        $form[$breakpoint_option]['#options'][$prefix . "-$width"] = $this->formatPlural(12 / $width, '@width (@count column per row)', '@width (@count columns per row)', ['@width' => $width]);
      }
    }
  }

}
