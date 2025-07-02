<?php

namespace Drupal\better_exposed_filters\Plugin\better_exposed_filters\pager;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\better_exposed_filters\Plugin\BetterExposedFiltersWidgetBase;
use Drupal\better_exposed_filters\Plugin\BetterExposedFiltersWidgetInterface;

/**
 * Base class for Better exposed pager widget plugins.
 */
abstract class PagerWidgetBase extends BetterExposedFiltersWidgetBase implements BetterExposedFiltersWidgetInterface {

  use StringTranslationTrait;

  /**
   * List of available exposed sort form element keys.
   *
   * @var array
   */
  protected array $pagerElements = [
    'items_per_page',
    'offset',
  ];

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return parent::defaultConfiguration() + [
      'advanced' => [
        'is_secondary' => FALSE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(mixed $handler = NULL, array $options = []): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = [];

    $form['advanced']['is_secondary'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('This is a secondary option'),
      '#default_value' => !empty($this->configuration['advanced']['is_secondary']),
      '#states' => [
        'visible' => [
          ':input[name="exposed_form_options[bef][general][allow_secondary]"]' => ['checked' => TRUE],
        ],
      ],
      '#description' => $this->t('Places this element in the secondary options portion of the exposed form.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state): void {
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
