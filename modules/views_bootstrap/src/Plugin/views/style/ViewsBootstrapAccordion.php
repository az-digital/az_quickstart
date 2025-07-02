<?php

namespace Drupal\views_bootstrap\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render each item as a row in a Bootstrap Accordion.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "views_bootstrap_accordion",
 *   title = @Translation("Bootstrap Accordion"),
 *   help = @Translation("Displays rows in a Bootstrap Accordion."),
 *   theme = "views_bootstrap_accordion",
 *   theme_file = "../views_bootstrap.theme.inc",
 *   display_types = {"normal"}
 * )
 */
class ViewsBootstrapAccordion extends StylePluginBase {
  /**
   * Does the style plugin for itself support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Definition.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['panel_title_field'] = ['default' => NULL];

    return $options;
  }

  /**
   * Render the given style.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    if (isset($form['grouping'])) {
      unset($form['grouping']);

      $form['panel_title_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Panel title field'),
        '#options' => $this->displayHandler->getFieldLabels(TRUE),
        '#required' => TRUE,
        '#default_value' => $this->options['panel_title_field'],
        '#description' => $this->t('Select the field that will be used as the accordian panel titles.'),
      ];
    }
    $options_select = [
      '0' => $this->t('Collapsed'),
      '1' => $this->t('Uncollapsed'),
    ];
    $form['collapse'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Collapse options'),
    ];
    $form['collapse']['first'] = [
      '#type' => 'select',
      '#title' => $this->t('First element'),
      '#options' => $options_select,
      '#default_value' => $this->options['collapse']['first'],
      '#description' => $this->t('To collapse/uncollapse the first element of the list. If there is only one item, first element settings prevails than the others (middle, last)'),
    ];
    $form['collapse']['middle'] = [
      '#type' => 'select',
      '#title' => $this->t('Middle elements'),
      '#options' => $options_select,
      '#default_value' => $this->options['collapse']['middle'],
      '#description' => $this->t('To collapse/uncollapse the middle elements of the list.'),
    ];
    $form['collapse']['last'] = [
      '#type' => 'select',
      '#title' => $this->t('Last element'),
      '#options' => $options_select,
      '#default_value' => $this->options['collapse']['last'],
      '#description' => $this->t('To collapse/uncollapse the last element of the list.'),
    ];
  }

}
