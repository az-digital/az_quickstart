<?php

namespace Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter;

use Drupal\Core\Form\FormStateInterface;

/**
 * Basic number widget. Input field but with type="number".
 *
 * @BetterExposedFiltersFilterWidget(
 *   id = "bef_number",
 *   label = @Translation("Number"),
 * )
 */
class Number extends FilterWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(mixed $handler = NULL, array $options = []): bool {
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $handler */
    $is_applicable = FALSE;

    if (is_a($handler, 'Drupal\views\Plugin\views\filter\NumericFilter')) {
      $is_applicable = TRUE;
    }

    return $is_applicable;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return parent::defaultConfiguration() + [
      'min' => NULL,
      'max' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    unset($form['advanced']['placeholder_text']);
    $form['min'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum'),
      '#default_value' => $this->configuration['min'],
      '#description' => $this->t('Adds a min attribute to the input field.'),
    ];

    $form['max'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum'),
      '#default_value' => $this->configuration['max'],
      '#description' => $this->t('Adds a max attribute to the input field.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state): void {
    // Set the number field.
    $field_id = $this->getExposedFilterFieldId();

    // Handle wrapper element added to exposed filters
    // in https://www.drupal.org/project/drupal/issues/2625136.
    $wrapper_id = $field_id . '_wrapper';
    if (!isset($form[$field_id]) && isset($form[$wrapper_id])) {
      $element = &$form[$wrapper_id][$field_id];
    }
    else {
      $element = &$form[$field_id];
    }

    parent::exposedFormAlter($form, $form_state);

    // Double Number-API-based input elements such as "in-between".
    $is_between = isset($element['min']) && isset($element['max']) && 'textfield' == $element['min']['#type'] && 'textfield' == $element['max']['#type'];

    if ($is_between) {
      $element['max']['#theme'] = 'bef_number';
      $element['min']['#theme'] = 'bef_number';
      $element['max']['#type'] = 'number';
      $element['min']['#type'] = 'number';
      $element['max']['#attributes']['class'][] = 'bef-number';
      $element['min']['#attributes']['class'][] = 'bef-number';

      $max = $this->configuration['max'];
      if ($max) {
        $element['max']['#attributes']['max'] = $max;
      }

      $min = $this->configuration['min'];
      if ($min) {
        $element['min']['#attributes']['min'] = $min;
      }
    }
    else {
      $element['#theme'] = 'bef_number';
      $element['#type'] = 'number';
      $element['#attributes']['class'][] = 'bef-number';

      $max = $this->configuration['max'];
      if ($max) {
        $element['#attributes']['max'] = $max;
      }

      $min = $this->configuration['min'];
      if ($min) {
        $element['#attributes']['min'] = $min;
      }
    }
  }

}
