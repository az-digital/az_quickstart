<?php

namespace Drupal\smart_date\Plugin\views\filter;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\date_popup\DatePopupHelper;
use Drupal\views\FieldAPIHandlerTrait;
use Drupal\views\Plugin\views\filter\Date as CoreDate;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Date/time views filter, with granularity patch applied.
 *
 * Even thought dates are stored as strings, the numeric filter is extended
 * because it provides more sensible operators.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("date")
 */
class Date extends CoreDate implements ContainerFactoryPluginInterface {

  use FieldAPIHandlerTrait;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The request stack used to determine current time.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new Date handler.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack used to determine the current time.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DateFormatterInterface $date_formatter, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->dateFormatter = $date_formatter;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('date.formatter'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // Value is already set up properly, we're just adding our new field to it.
    $options['value']['contains']['granularity']['default'] = 'second';

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function operators(): array {
    $add_operators = FALSE;
    // Only attempt to retrieve field type if necessary parameters are met.
    if (method_exists($this, 'getFieldDefinition') && !empty($this->definition['field_name'])) {
      if ($this->getFieldDefinition()?->getType() === "smartdate") {
        $add_operators = TRUE;
      }
    }
    if (!$add_operators) {
      return parent::operators();
    }
    return parent::operators() + [
      'daterange_contains' => [
        'title' => $this->t('Contains'),
        'method' => 'opContains',
        'short' => $this->t('contains'),
        'values' => 1,
      ],
      'daterange_overlaps' => [
        'title' => $this->t('Overlaps'),
        'method' => 'opContains',
        'short' => $this->t('overlaps'),
        'values' => 1,
      ],
      'daterange_not_contains' => [
        'title' => $this->t('Does not contain'),
        'method' => 'opContains',
        'short' => $this->t('not contains'),
        'values' => 1,
      ],
      'daterange_starts_or_ends' => [
        'title' => $this->t('Starts or ends at'),
        'method' => 'opContains',
        'short' => $this->t('starts or ends'),
        'values' => 1,
      ],
      'daterange_contains_range' => [
        'title' => $this->t('Contains range'),
        'method' => 'opContainsRange',
        'short' => $this->t('contains range'),
        'values' => 2,
      ],
      'daterange_not_contains_range' => [
        'title' => $this->t('Does not contain range'),
        'method' => 'opContainsRange',
        'short' => $this->t('not contains range'),
        'values' => 2,
      ],
      'daterange_starts_or_ends_range' => [
        'title' => $this->t('Starts or ends at range'),
        'method' => 'opContainsRange',
        'short' => $this->t('starts or ends range'),
        'values' => 2,
      ],
    ];
  }

  /**
   * Add a granularity selector to the value form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $options = [
      'second' => $this->t('Second'),
      'minute' => $this->t('Minute'),
      'hour'   => $this->t('Hour'),
      'day'    => $this->t('Day'),
      'month'  => $this->t('Month'),
      'year'   => $this->t('Year'),
    ];

    $form['value']['granularity'] = [
      '#type' => 'radios',
      '#title' => $this->t('Granularity'),
      '#options' => $options,
      '#description' => $this->t('The granularity is the smallest unit to use when determining whether two dates are the same; for example, if the granularity is "Year" then all dates in 1999, regardless of when they fall in 1999, will be considered the same date.'),
      '#default_value' => $this->options['value']['granularity'],
      '#weight' => 10,
    ];
  }

  /**
   * Override parent method, which deals with dates as integers.
   */
  protected function opBetween($field) {
    [$min_value, $max_value] = $this->getMinAndMax(FALSE);
    $operator = strtoupper($this->operator);
    $this->query->addWhereExpression($this->options['group'], "$field $operator $min_value AND $max_value");
  }

  /**
   * Override parent method, to add granularity options.
   */
  protected function opSimple($field) {
    [$min_value, $max_value] = $this->getMinAndMax();
    $operator = $this->operator;
    if ($this->options['value']['granularity'] !== 'second') {
      // Additional, operator-specific logic.
      if ($operator[0] === '>') {
        $value = $min_value;
      }
      elseif ($operator[0] === '<') {
        $value = $max_value;
      }
      else {
        if ($operator === '=') {
          $operator = 'BETWEEN';
        }
        elseif ($operator === '!=') {
          $operator = 'NOT BETWEEN';
        }
        $this->query->addWhereExpression($this->options['group'], "$field $operator $min_value AND $max_value");
        return;
      }
    }
    else {
      $value = $min_value;
    }
    // This is safe because we forced the provided value to a DateTimePlus.
    $this->query->addWhereExpression($this->options['group'], "$field $operator $value");
  }

  /**
   * Add conditions to the query.
   */
  protected function opContains($field): void {
    [$min_value, $max_value] = $this->getMinAndMax();
    $this->containsConditions($field, $min_value, $max_value);
  }

  /**
   * Add conditions to the query.
   */
  protected function opContainsRange($field): void {
    [$min_value, $max_value] = $this->getMinAndMax(FALSE);
    $this->containsConditions($field, $min_value, $max_value);
  }

  /**
   * Helper function to add the conditions to the query.
   *
   * @param string $field
   *   The field name.
   * @param string $min_value
   *   The minimum date(time) value.
   * @param string $max_value
   *   The maximum date(time) value.
   */
  protected function containsConditions(string $field, string $min_value, string $max_value): void {
    if (strpos($field, '_end_value') !== FALSE) {
      // Filter is using the end value, so adjust variables accordingly.
      $field_end = $field;
      $field = substr_replace($field, '_value', strrpos($field, '_end_value'));
    }
    else {
      $field_end = substr_replace($field, '_end_value', strrpos($field, '_value'));
    }
    switch ($this->operator) {
      case 'daterange_contains':
        $this->query->addWhereExpression($this->options['group'], "$field <= $min_value AND $field_end >= $max_value");
        break;

      case 'daterange_overlaps':
        $this->query->addWhereExpression($this->options['group'], "$field <= $max_value AND $field_end >= $min_value");
        break;

      case 'daterange_not_contains':
        $this->query->addWhereExpression($this->options['group'], "$field >= $max_value OR $field_end <= $min_value");
        break;

      case 'daterange_starts_or_ends':
        $this->query->addWhereExpression($this->options['group'], "($field >= $min_value AND $field <= $max_value) OR ($field_end >= $min_value AND $field_end <= $max_value)");
        break;
    }
  }

  /**
   * Get the proper time zone to use in computations.
   *
   * Date-only fields do not have a time zone associated with them, so the
   * filter input needs to use UTC for reference. Otherwise, use the time zone
   * for the current user.
   *
   * @return string
   *   The time zone name.
   */
  protected function getTimezone() {
    return date_default_timezone_get();
  }

  /**
   * Helper function to prepare min and max values for op* callbacks.
   *
   * @param bool $singleValueMode
   *   TRUE, if the values should be calculated on the single "value" field.
   *   Otherwise, set to FALSE to calculate based on "min" and "max" values.
   *
   * @return array
   *   An array containing the fully prepared min and max values ready to be
   *   used by query conditions.
   */
  protected function getMinAndMax(bool $singleValueMode = TRUE): array {
    $timezone = $this->getTimezone();
    $granularity = $this->options['value']['granularity'];

    // Convert form field value(s) to DateTimePlus for additional processing.
    if ($singleValueMode) {
      $a = $b = new DateTimePlus($this->value['value'], new \DateTimeZone($timezone));
    }
    else {
      $a = new DateTimePlus($this->value['min'], new \DateTimeZone($timezone));
      $b = new DateTimePlus($this->value['max'], new \DateTimeZone($timezone));
    }
    $min = [
      'year' => $a->format('Y'),
      'month' => $a->format('n'),
      'day' => $a->format('j'),
      'hour' => $a->format('G'),
      'minute' => $a->format('i'),
      'second' => $a->format('s'),
    ];
    $max = [
      'year' => $b->format('Y'),
      'month' => $b->format('n'),
      'day' => $b->format('j'),
      'hour' => $b->format('G'),
      'minute' => $b->format('i'),
      'second' => $b->format('s'),
    ];
    switch ($granularity) {
      case 'year':
        $min['month'] = '01';
        $max['month'] = '12';
        $max['day'] = '31';
      case 'month':
        $min['day'] = '01';
        if ($granularity !== 'year') {
          $max['day'] = $b->format('t');
        }
      case 'day':
        $min['hour'] = '00';
        $max['hour'] = '23';
      case 'hour':
        $min['minute'] = '00';
        $max['minute'] = '59';
      case 'minute':
        $min['second'] = '00';
        $max['second'] = '59';
    }
    $min_value = $a::createFromArray($min, $timezone)->format('U');
    $max_value = $b::createFromArray($max, $timezone)->format('U');
    return [$min_value, $max_value];
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    parent::buildExposedForm($form, $form_state);
    // If Date Popup is installed, apply its popup to the filter.
    if (class_exists('\Drupal\date_popup\DatePopupHelper')) {
      DatePopupHelper::applyDatePopup($form, $this->options);
    }
  }

}
