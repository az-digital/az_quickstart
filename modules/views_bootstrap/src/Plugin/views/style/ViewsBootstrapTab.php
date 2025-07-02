<?php

namespace Drupal\views_bootstrap\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render each item in an ordered or unordered list.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "views_bootstrap_tab",
 *   title = @Translation("Bootstrap Tab"),
 *   help = @Translation("Displays rows in Bootstrap Tabs."),
 *   theme = "views_bootstrap_tab",
 *   theme_file = "../views_bootstrap.theme.inc",
 *   display_types = {"normal"}
 * )
 */
class ViewsBootstrapTab extends StylePluginBase {

  /**
   * Does the style plugin for itself support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Definition.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['tab_field'] = ['default' => NULL];
    $options['tab_type'] = ['default' => 'tabs'];
    $options['justified'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    if (isset($form['grouping'])) {
      unset($form['grouping']);

      $form['tab_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Tab field'),
        '#options' => $this->displayHandler->getFieldLabels(TRUE),
        '#required' => TRUE,
        '#default_value' => $this->options['tab_field'],
        '#description' => $this->t('Select the field that will be used as the tab.'),
      ];

      $form['tab_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Tab Type'),
        '#options' => [
          'tabs' => $this->t('Tabs'),
          'pills' => $this->t('Pills'),
          'list' => $this->t('List'),
        ],
        '#required' => TRUE,
        '#default_value' => $this->options['tab_type'],
      ];

      $form['justified'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Justified'),
        '#default_value' => $this->options['justified'],
        '#description' => $this->t('Make tabs equal widths of their parent'),
      ];
    }

  }

}
