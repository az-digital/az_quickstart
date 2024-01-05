<?php

namespace Drupal\az_exposed_filters\Plugin\az_exposed_filters\pager;

use Drupal\az_exposed_filters\Plugin\AzExposedFiltersWidgetBase;
use Drupal\az_exposed_filters\Plugin\AzExposedFiltersWidgetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for AZ exposed pager widget plugins.
 */
abstract class PagerWidgetBase extends AzExposedFiltersWidgetBase implements AzExposedFiltersWidgetInterface {

  use StringTranslationTrait;

  /**
   * List of available exposed sort form element keys.
   *
   * @var array
   */
  protected $pagerElements = [
    'items_per_page',
    'offset',
  ];

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'advanced' => [
        'is_secondary' => FALSE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable($handler = NULL, array $options = []) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $form['advanced']['is_secondary'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('This is a secondary option'),
      '#default_value' => !empty($this->configuration['advanced']['is_secondary']),
      '#states' => [
        'visible' => [
          ':input[name="exposed_form_options[az_exposed_filters][general][allow_secondary]"]' => ['checked' => TRUE],
        ],
      ],
      '#description' => $this->t('Places this element in the secondary options portion of the exposed form.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state) {
    $is_secondary = !empty($form['secondary']) && $this->configuration['advanced']['is_secondary'];

    foreach ($this->pagerElements as $element) {
      // Sanity check to make sure the element exists.
      if (empty($form[$element])) {
        continue;
      }

      if ($is_secondary) {
        $this->addElementToGroup($form, $form_state, $element, 'secondary');
      }

      // Finally, add some metadata to the form element.
      $this->addContext($form[$element]);
    }
  }

}
