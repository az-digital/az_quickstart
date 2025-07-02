<?php

namespace Drupal\asset_injector\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the duplication of JavaScript asset injector.
 *
 * @package Drupal\asset_injector\Form
 */
class AssetInjectorJsForm extends AssetInjectorFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\asset_injector\Entity\AssetInjectorJs $entity */
    $entity = $this->entity;

    // Add JS specific information about the wrapping element:
    $form['code']['#description'] .= ' ' . $this->t('Do NOT include the wrapping %script element.', ['%script' => '<script>']);

    // Advanced options fieldset.
    $form['advanced'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Advanced options'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#tree' => FALSE,
    ];

    $form['advanced']['jquery'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Requires jQuery'),
      '#description' => $this->t('Not all pages load jQuery by default. Select this to include the jQuery library when loading this asset.'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#default_value' => $entity->jquery,
    ];

    $form['advanced']['preprocess'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Preprocess JS'),
      '#description' => $this->t('If the JS is preprocessed, and JS aggregation is enabled, the script file will be aggregated to improve performance and caching.'),
      '#default_value' => $entity->preprocess,
    ];

    $form['advanced']['header'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable to load the script in the header, otherwise it will be loaded in the footer (Drupal default)
        to improve performance.'),
      '#default_value' => $entity->header,
    ];

    $form['advanced']['use_noscript'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add additional %noscript fallback element', ['%noscript' => '<noscript>']),
      '#description' => $this->t('Allows to enter the contents for an additional %noscript element which is added to the page (if rules apply).
        %noscript content is displayed for clients with JavaScript disabled.', ['%noscript' => '<noscript>']),
      '#default_value' => empty(array_filter($entity->noscriptRegion)) ? 0 : 1,
    ];

    $form['advanced']['noscript_wrap'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('%tag code', ['%tag' => '<noscript>']),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#states' => [
        'visible' => [
          'input[name="use_noscript"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['advanced']['noscript_wrap']['noscript'] = [
      '#type' => 'textarea',
      '#title' => $this->t('%tag code', ['%tag' => '<noscript>']),
      '#title_display' => 'invisible',
      '#default_value' => $entity->noscript,
      '#description' => $this->t('This code will be wrapped into %tag tags', ['%tag' => '<noscript>']),
      '#rows' => 5,
    ];

    $form['advanced']['noscript_wrap']['noscriptRegion'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];

    foreach ($this->themeHandler->listInfo() as $name => $theme) {
      if (isset($theme->info['hidden']) && $theme->info['hidden']) {
        continue;
      }

      $form['advanced']['noscript_wrap']['noscriptRegion'][$name] = [
        '#type' => 'select',
        '#title' => $this->t('noscript Region for %theme Theme', ['%theme' => $theme->info['name']]),
        '#description' => $this->t('Which region should load the %tag code?', ['%tag' => '<noscript>']),
        '#options' => $theme->info['regions'],
        "#empty_option" => $this->t('-- None --'),
        '#multiple' => FALSE,
        '#default_value' => $entity->noscriptRegion[$name] ?? NULL,
      ];
    }

    $form['code']['#attributes']['data-ace-mode'] = 'javascript';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // Clear noscriptRegion if use_noscript is unchecked.
    if (!$form_state->getValue('use_noscript')) {
      $form_state->setValue('noscriptRegion', []);
    }
  }

}
