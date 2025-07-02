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
 *   id = "views_bootstrap_list_group",
 *   title = @Translation("Bootstrap List Group"),
 *   help = @Translation("Displays rows in a Bootstrap List Group."),
 *   theme = "views_bootstrap_list_group",
 *   theme_file = "../views_bootstrap.theme.inc",
 *   display_types = {"normal"}
 * )
 */
class ViewsBootstrapListGroup extends StylePluginBase {

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
    $options['link_field'] = ['default' => []];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $fields = ['' => $this->t('<None>')];
    $fields += $this->displayHandler->getFieldLabels(TRUE);

    $form['title_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Title field'),
      '#options' => $fields,
      '#required' => FALSE,
      '#default_value' => $this->options['title_field'],
      '#description' => $this->t('Select the field that will be used as the title.'),
    ];

  }

}
