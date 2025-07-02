<?php

namespace Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;

/**
 * Sliders widget implementation.
 *
 * @BetterExposedFiltersFilterWidget(
 *   id = "bef_sliders",
 *   label = @Translation("Sliders"),
 * )
 */
class Sliders extends FilterWidgetBase {

  // Slider animation options.
  const ANIMATE_NONE = 0;
  const ANIMATE_SLOW = 600;
  const ANIMATE_NORMAL = 400;
  const ANIMATE_FAST = 200;
  const ANIMATE_CUSTOM = 'custom';

  // Slider orientation options.
  const ORIENTATION_HORIZONTAL = 'horizontal';
  const ORIENTATION_VERTICAL = 'vertical';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return parent::defaultConfiguration() + [
      'min' => 0,
      'max' => 99999,
      'step' => 1,
      'animate' => self::ANIMATE_NONE,
      'animate_ms' => 0,
      'orientation' => self::ORIENTATION_HORIZONTAL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(mixed $handler = NULL, array $options = []): bool {
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $handler */
    $is_applicable = FALSE;

    // The date filter handler extends the numeric filter handler, so we have
    // to exclude it specifically.
    $is_numeric_filter = is_a($handler, 'Drupal\views\Plugin\views\filter\NumericFilter');
    $is_range_filter = is_a($handler, 'Drupal\range\Plugin\views\filter\Range');
    $is_date_filter = is_a($handler, 'Drupal\views\Plugin\views\filter\Date');
    if (($is_numeric_filter || $is_range_filter) && !$is_date_filter && !$handler->isAGroup()) {
      $is_applicable = TRUE;
    }

    return $is_applicable;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    $filter = $this->handler;

    $form = parent::buildConfigurationForm($form, $form_state);

    $form['min'] = [
      '#type' => 'number',
      '#title' => $this->t('Range minimum'),
      '#default_value' => $this->configuration['min'],
      '#description' => $this->t('The minimum allowed value for the range slider. It can be positive, negative, or zero and have up to 11 decimal places.'),
    ];

    $form['max'] = [
      '#type' => 'number',
      '#title' => $this->t('Range maximum'),
      '#default_value' => $this->configuration['max'],
      '#description' => $this->t('The maximum allowed value for the range slider. It can be positive, negative, or zero and have up to 11 decimal places.'),
    ];

    $form['step'] = [
      '#type' => 'number',
      '#title' => $this->t('Step'),
      '#default_value' => $this->configuration['step'],
      '#description' => $this->t('Determines the size or amount of each interval or step the slider takes between the min and max.') . '<br />' . $this->t('The full specified value range of the slider (Range maximum - Range minimum) must be evenly divisible by the step.') . '<br />' . $this->t('The step must be a positive number of up to 5 decimal places.'),
      '#min' => 0,
    ];

    $form['animate'] = [
      '#type' => 'select',
      '#title' => $this->t('Animation speed'),
      '#options' => [
        self::ANIMATE_NONE => $this->t('None'),
        self::ANIMATE_SLOW => $this->t('Slow (600 ms)'),
        self::ANIMATE_NORMAL => $this->t('Normal (400 ms)'),
        self::ANIMATE_FAST => $this->t('Fast (200 ms)'),
        self::ANIMATE_CUSTOM => $this->t('Custom'),
      ],
      '#default_value' => $this->configuration['animate'],
      '#description' => $this->t('Whether to slide handle smoothly when user click outside handle on the bar.'),
    ];

    $form['animate_ms'] = [
      '#type' => 'number',
      '#title' => $this->t('Animation speed in milliseconds'),
      '#default_value' => $this->configuration['animate_ms'],
      '#description' => $this->t('The number of milliseconds to run the animation (e.g. 1000).'),
      '#states' => [
        'visible' => [
          ':input[name="exposed_form_options[bef][filter][' . $filter->field . '][configuration][animate]"]' => ['value' => self::ANIMATE_CUSTOM],
        ],
      ],
    ];

    $form['orientation'] = [
      '#type' => 'select',
      '#title' => $this->t('Orientation'),
      '#options' => [
        self::ORIENTATION_HORIZONTAL => $this->t('Horizontal'),
        self::ORIENTATION_VERTICAL => $this->t('Vertical'),
      ],
      '#default_value' => $this->configuration['orientation'],
      '#description' => $this->t('The orientation of the range slider.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::validateConfigurationForm($form, $form_state);

    // Max must be > min.
    $min = $form_state->getValue('min');
    $max = $form_state->getValue('max');
    if (!empty($min) && $max <= $min) {
      $form_state->setError($form['max'], $this->t('The slider max value must be greater than the slider min value.'));
    }

    // Step must have:
    // - No more than 5 decimal places.
    // - Slider range must be evenly divisible by step.
    $step = $form_state->getValue('step');
    if (strlen(substr(strrchr((string) $step, '.'), 1)) > 5) {
      $form_state->setError($form['step'], $this->t('The slider step option for %name cannot have more than 5 decimal places.'));
    }

    // Very small step and a very large range can go beyond the max value of
    // an int in PHP. Thus, we look for a decimal point when casting the result
    // to a string.
    if (strpos((string) ($max - $min) / $step, '.')) {
      $form_state->setError($form['step'], $this->t('The slider range must be evenly divisible by the step option.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state): void {
    $field_id = $this->getExposedFilterFieldId();

    parent::exposedFormAlter($form, $form_state);

    // Attach the JS (@see /js/sliders.js)
    $form[$field_id]['#attached']['library'][] = 'better_exposed_filters/sliders';

    // Set the slider settings.
    $form[$field_id]['#attached']['drupalSettings']['better_exposed_filters']['slider'] = TRUE;
    $form[$field_id]['#attached']['drupalSettings']['better_exposed_filters']['slider_options'][$field_id] = [
      'min' => $this->configuration['min'],
      'max' => $this->configuration['max'],
      'step' => $this->configuration['step'],
      'animate' => ($this->configuration['animate'] === self::ANIMATE_CUSTOM) ? $this->configuration['animate_ms'] : $this->configuration['animate'],
      'orientation' => $this->configuration['orientation'],
      'id' => Html::getUniqueId($field_id),
      'dataSelector' => Html::getId($field_id),
      'viewId' => $form['#id'],
    ];
  }

}
