<?php

namespace Drupal\views_bootstrap\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\Table;

/**
 * Style plugin to render each item as a row in a Bootstrap table.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "views_bootstrap_table",
 *   title = @Translation("Bootstrap Table"),
 *   help = @Translation("Displays rows in a Bootstrap table."),
 *   theme = "views_bootstrap_table",
 *   theme_file = "../views_bootstrap.theme.inc",
 *   display_types = {"normal"}
 * )
 */
class ViewsBootstrapTable extends Table {

  /**
   * Definition.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['bootstrap_styles'] = ['default' => []];
    $options['responsive'] = ['default' => FALSE];

    return $options;
  }

  /**
   * Render the given style.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['responsive'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Responsive'),
      '#default_value' => $this->options['responsive'],
      '#description' => $this->t('To make a table scroll horizontally on small devices.'),
    ];

    $form['bootstrap_styles'] = [
      '#title' => $this->t('Bootstrap styles'),
      '#type' => 'checkboxes',
      '#default_value' => $this->options['bootstrap_styles'],
      '#options' => [
        'striped' => $this->t('Striped'),
        'bordered' => $this->t('Bordered'),
        'hover' => $this->t('Hover'),
        'condensed' => $this->t('Condensed'),
      ],
    ];
  }

}
