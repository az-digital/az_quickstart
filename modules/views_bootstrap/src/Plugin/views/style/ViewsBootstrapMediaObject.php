<?php

namespace Drupal\views_bootstrap\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render each item as a row in a Bootstrap Media Object.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "views_bootstrap_media_object",
 *   title = @Translation("Bootstrap Media Object"),
 *   help = @Translation("Displays rows in a Bootstrap Media Object."),
 *   theme = "views_bootstrap_media_object",
 *   theme_file = "../views_bootstrap.theme.inc",
 *   display_types = {"normal"}
 * )
 */
class ViewsBootstrapMediaObject extends StylePluginBase {

  /**
   * Does the style plugin for itself support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * Definition.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['image_field'] = ['default' => []];
    $options['heading_field'] = ['default' => []];
    $options['body_field'] = ['default' => []];
    $options['image_class'] = ['default' => 'media-left'];

    return $options;
  }

  /**
   * Render the given style.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $fields = $this->displayHandler->getFieldLabels(TRUE);
    $optionalFields = ['' => $this->t('<None>')];
    $optionalFields += $this->displayHandler->getFieldLabels(TRUE);

    $form['heading_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Heading field'),
      '#options' => $fields,
      '#required' => TRUE,
      '#default_value' => $this->options['heading_field'],
      '#description' => $this->t('Select the field that will be used as the media object heading.'),
    ];

    $form['image_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Image field'),
      '#options' => $this->displayHandler->getFieldLabels(TRUE),
      '#required' => TRUE,
      '#default_value' => $this->options['image_field'],
      '#description' => $this->t('Select the field that will be used as the media object image.'),
    ];

    $form['image_class'] = [
      '#type' => 'radios',
      '#title' => $this->t('Image Alignment'),
      '#options' => [
        'media-left' => $this->t('Left'),
        'media-right' => $this->t('Right'),
        'media-middle' => $this->t('Middle'),
      ],
      '#default_value' => $this->options['image_class'],
      '#description' => $this->t('Align the media object image left or right.'),
    ];

    $form['body_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Body field'),
      '#options' => $optionalFields,
      '#required' => FALSE,
      '#default_value' => $this->options['body_field'],
      '#description' => $this->t('Select the field that will be used as the media object body.'),
    ];

  }

}
