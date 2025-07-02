<?php

namespace Drupal\asset_injector\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Config entity form for css assets.
 *
 * @package Drupal\asset_injector\Form
 */
class AssetInjectorCssForm extends AssetInjectorFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\asset_injector\Entity\AssetInjectorCss $entity */
    $entity = $this->entity;

    // Add CSS specific information about the wrapping element:
    $form['code']['#description'] .= ' ' . $this->t('Do NOT include the wrapping %style element.', ['%style' => '<style>']);

    // Advanced options fieldset.
    $form['advanced'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Advanced options'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#tree' => FALSE,
    ];

    $form['advanced']['media'] = [
      '#type' => 'select',
      '#title' => 'Media',
      '#description' => $this->t('Which media types is the CSS used.'),
      '#options' => [
        'all' => $this->t('All'),
        'print' => $this->t('Print'),
        'screen' => $this->t('Screen'),
      ],
      '#default_value' => $entity->media,
    ];

    $form['advanced']['preprocess'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Preprocess CSS'),
      '#description' => $this->t('If the CSS is preprocessed, and CSS aggregation is enabled, the script file will be aggregated.'),
      '#default_value' => $entity->preprocess,
    ];
    $form['code']['#attributes']['data-ace-mode'] = 'css';
    return $form;
  }

}
