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
 *   id = "views_bootstrap_cards",
 *   title = @Translation("Bootstrap Cards"),
 *   help = @Translation("Displays rows in a Bootstrap Card Group layout"),
 *   theme = "views_bootstrap_cards",
 *   theme_file = "../views_bootstrap.theme.inc",
 *   display_types = {"normal"}
 * )
 */
class ViewsBootstrapCards extends StylePluginBase {
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
    $options['card_title_field'] = ['default' => NULL];
    $options['card_content_field'] = ['default' => NULL];
    $options['card_image_field'] = ['default' => NULL];
    return $options;
  }

  /**
   * Render the given style.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    if (isset($form['grouping'])) {
      unset($form['grouping']);
      $form['card_title_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Card title field'),
        '#options' => $this->displayHandler->getFieldLabels(TRUE),
        '#required' => TRUE,
        '#default_value' => $this->options['card_title_field'],
        '#description' => $this->t('Select the field that will be used for the card title.'),
      ];
      $form['card_content_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Card content field'),
        '#options' => $this->displayHandler->getFieldLabels(TRUE),
        '#required' => TRUE,
        '#default_value' => $this->options['card_content_field'],
        '#description' => $this->t('Select the field that will be used for the card content.'),
      ];
      $form['card_image_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Card image field'),
        '#options' => $this->displayHandler->getFieldLabels(TRUE),
        '#required' => TRUE,
        '#default_value' => $this->options['card_image_field'],
        '#description' => $this->t('Select the field that will be used for the card image.'),
      ];
    }
  }

}
