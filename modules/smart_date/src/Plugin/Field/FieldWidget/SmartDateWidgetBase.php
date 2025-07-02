<?php

namespace Drupal\smart_date\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeWidgetBase;
use Drupal\datetime_range\Plugin\Field\FieldWidget\DateRangeWidgetBase;
use Drupal\smart_date\Plugin\Field\FieldType\SmartDateListItemBase;
use Drupal\smart_date\SmartDatePluginTrait;
use Drupal\smart_date_recur\Entity\SmartDateRule;

/**
 * Base class for the 'smartdate_*' widgets.
 */
class SmartDateWidgetBase extends DateTimeWidgetBase {

  use SmartDatePluginTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'show_extra' => FALSE,
      'hide_date' => TRUE,
      'allday' => TRUE,
      'remove_seconds' => FALSE,
      'duration_overlay' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $cardinality = $this->fieldDefinition
      ->getFieldStorageDefinition()
      ->getCardinality();
    if ($cardinality != 1) {
      $element['show_extra'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Always include an empty widget (Drupal default). Otherwise the user must explicitly add a new widget if needed.'),
        '#default_value' => $this->getSetting('show_extra'),
      ];
    }

    $element['hide_date'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Hide the end date field unless it's different from the start date."),
      '#default_value' => $this->getSetting('hide_date'),
    ];

    $element['allday'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Provide a checkbox to make an event all day."),
      '#default_value' => $this->getSetting('allday'),
    ];

    $element['remove_seconds'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Remove any seconds, if present, from existing values."),
      '#default_value' => $this->getSetting('remove_seconds'),
    ];

    $element['duration_overlay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Use an overlay to display duration options."),
      '#default_value' => $this->getSetting('duration_overlay'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $cardinality = $this->fieldDefinition
      ->getFieldStorageDefinition()
      ->getCardinality();
    if ($cardinality != 1 && !$this->getSetting('show_extra')) {
      $summary[] = $this->t('Suppress extra, empty widget.');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $values = [];

    $field_def = $this->fieldDefinition;
    $field_type = $field_def->getType();
    $allow_recurring = FALSE;
    if ($field_type == 'smartdate') {
      if ($field_def instanceof FieldConfigInterface) {
        $allow_recurring = $field_def->getThirdPartySetting('smart_date_recur', 'allow_recurring');
      }
      elseif ($field_def instanceof BaseFieldDefinition) {
        // @todo Document that for custom entities, you must enable recurring
        // functionality by adding ->setSetting('allow_recurring', TRUE)
        // to your field definition.
        $allow_recurring = $field_def->getSetting('allow_recurring');
      }

      // @todo more elegant way to handle hiding recurring instances?
      if ($allow_recurring && $items[$delta]->rrule) {
        $rrule = SmartDateRule::load($items[$delta]->rrule);
        // @todo log nonexistent rrule values?
        if ($rrule) {
          if (isset($form['#rules_processed'][$items[$delta]->rrule])) {
            // Not the first instance, so skip this delta.
            $element['#access'] = FALSE;
            return $element;
          }
          else {
            // Keep track of this rule as having been processed.
            $form['#rules_processed'][$items[$delta]->rrule] = $items[$delta]->rrule;
            $items[$delta]->value = (int) $rrule->start->getString();
            $items[$delta]->end_value = (int) $rrule->end->getString();
            $items[$delta]->duration = ($items[$delta]->end_value - $items[$delta]->value) / 60;
          }
        }
      }
      $defaults = $this->fieldDefinition->getDefaultValueLiteral()[0];
      $timezone = $items[$delta]->timezone ?? date_default_timezone_get();
      $values['start'] = !empty($items[$delta]->value) ? DrupalDateTime::createFromTimestamp($items[$delta]->value, $timezone) : '';
      $values['end'] = !empty($items[$delta]->end_value) ? DrupalDateTime::createFromTimestamp($items[$delta]->end_value, $timezone) : '';
      $values['duration'] = $items[$delta]->duration ?? $defaults['default_duration'];
      $values['timezone'] = $items[$delta]->timezone ?? '';
    }
    elseif ($field_type == 'daterange') {
      if ($items[$delta]->start_date) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $start_date */
        $start_date = $items[$delta]->start_date;
        $values['start'] = $this->createNormalizedDefaultValue($start_date, $element['value']['#date_timezone']);
      }

      if ($items[$delta]->end_date) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
        $end_date = $items[$delta]->end_date;
        $values['end'] = $this->createNormalizedDefaultValue($end_date, $element['value']['#date_timezone']);
      }
      if (!empty($start_date) && !empty($end_date)) {
        $intervalFormatter = DrupalDateTime::createFromTimestamp(0);
        $timeInterval = $start_date->diff($end_date);
        $intervalInSeconds = $intervalFormatter->add($timeInterval)->getTimeStamp();
        $values['duration'] = $intervalInSeconds / 60;
      }
      $defaults = [];
      $default_duration = $this->getSetting('default_duration');
      if ($default_duration || $default_duration === 0 || $default_duration === '0') {
        $defaults['default_duration'] = $default_duration;
      }
      $default_duration_increments = $this->getSetting('default_duration_increments');
      if ($default_duration_increments) {
        $defaults['default_duration_increments'] = $default_duration_increments;
      }
    }
    $defaults['hide_date'] = $this->getSetting('hide_date');
    $defaults['allday'] = $this->getSetting('allday');
    $defaults['duration_overlay'] = $this->getSetting('duration_overlay');
    // If configured to, remove seconds from the values.
    if ($this->getSetting('remove_seconds') && $values) {
      foreach (['start', 'end'] as $which) {
        $date = $values[$which];
        if (empty($date)) {
          continue;
        }
        $values[$which] = $date->setTime($date->format("H"), $date->format("i"), '00');
      }
    }

    $values['storage'] = $field_type;
    $form['#attached']['library'][] = 'smart_date/smart_date';
    $element['#attributes']['class'][] = 'smartdate--widget';

    $this->createWidget($element, $values, $defaults);

    if ($allow_recurring && function_exists('smart_date_recur_widget_extra_fields')) {
      smart_date_recur_widget_extra_fields($element, $items[$delta], $this);
    }

    return $element;
  }

  /**
   * Helper method to create SmartDate element.
   */
  public static function createWidget(&$element, $values, ?array $defaults) {
    // If an empty set of defaults provided, create our own.
    if (empty($defaults)) {
      $defaults = [
        'default_duration_increments' => "30\n60|1 hour\n90\n120|2 hours\ncustom",
        'default_duration' => 60,
        'allday' => TRUE,
        'remove_seconds' => FALSE,
        'duration_overlay' => TRUE,
      ];
    }
    $limits_to_check = ['min', 'max'];
    foreach ($limits_to_check as $check) {
      if (!empty($defaults[$check]) && $limit = static::validateLimit($defaults[$check])) {
        $element['value']['#attributes'][$check] = $limit;
        $element['end_value']['#attributes'][$check] = $limit;
      }
    }
    // Wrap all of the select elements with a fieldset.
    $element['#theme_wrappers'][] = 'fieldset';

    $element['#element_validate'][] = [static::class, 'validateStartEnd'];
    $element['value']['#title'] = t('Start');
    // @todo Remove #date_year_range as of Drupal 10.1 when BIGINT will be used.
    $element['value']['#date_year_range'] = '1902:2037';
    if (isset($values['start'])) {
      // Ensure values always display relative to the site.
      $element['value']['#default_value'] = self::remapDatetime($values['start']);
    }

    $element['end_value'] = [
      '#title' => t('End'),
    ] + $element['value'];
    if (isset($values['end'])) {
      // Ensure values always display relative to the site.
      $element['end_value']['#default_value'] = self::remapDatetime($values['end']);
    }

    $element['value']['#attributes']['class'] = ['time-start'];
    $element['end_value']['#attributes']['class'] = ['time-end'];
    if (isset($values['storage'])) {
      $element['storage'] = [
        '#type' => 'value',
        '#value' => $values['storage'],
      ];
    }

    // Make the hide_date value available to the form.
    $element['end_value']['#attributes']['data-hide'] = (isset($defaults['hide_date']) && $defaults['hide_date']) ? 1 : 0;

    // Parse the allowed duration increments and create labels if not provided.
    $increments = SmartDateListItemBase::parseValues($defaults['default_duration_increments']);
    foreach ($increments as $key => $label) {
      if (strcmp($key, $label) !== 0) {
        // Label provided, so no extra logic required.
        continue;
      }
      if (is_numeric($key)) {
        // Anything but whole minutes will create errors with the time field.
        $num = (int) $key;
        $increments[$key] = t('@count minutes', ['@count' => $num]);
      }
      elseif ($key == 'custom') {
        $increments[$key] = t('Custom');
      }
      else {
        // Note sure what else we would encounter, so escape it.
        $increments[$key] = t('@key (unrecognized format)', ['@key' => $key]);
      }
    }
    $default_duration = $values['duration'] ?? $defaults['default_duration'];
    if (!array_key_exists($default_duration, $increments)) {
      if (array_key_exists('custom', $increments)) {
        $default_duration = 'custom';
      }
      else {
        // @todo throw some kind of error/warning if invalid duration?
        $default_duration = 0;
      }
    }
    $element['duration'] = [
      '#title' => t('Duration'),
      '#type' => 'select',
      '#options' => $increments,
      '#default_value' => $default_duration,
      '#attributes' => [
        'data-default' => $defaults['default_duration'],
        'class' => ['field-duration'],
      ],
      '#wrapper_attributes' => [
        'class' => ['duration-wrapper'],
      ],
    ];

    // Make the allday setting available to the form.
    $element['duration']['#attributes']['data-allday'] = (isset($defaults['allday']) && $defaults['allday']) ? 1 : 0;

    // Make the duration overlay setting available to the form.
    $element['duration']['#attributes']['data-overlay'] = (isset($defaults['duration_overlay']) && $defaults['duration_overlay']) ? 1 : 0;

    // No true input, so preserve an existing value otherwise use site default.
    $default_tz = (isset($values['timezone'])) ? $values['timezone'] : NULL;
    $element['timezone'] = [
      '#type' => 'hidden',
      '#title' => t('Time zone'),
      '#default_value' => $default_tz,
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // @phpstan-ignore-next-line
    $site_tz_name = \Drupal::config('system.date')->get('timezone.default');

    // The widget form element type has transformed the value to a
    // DrupalDateTime object at this point. We need to convert it back to the
    // storage timestamp.
    foreach ($values as &$item) {
      if (!isset($item['storage']) || $item['storage'] != 'smartdate') {
        // Check that the DateRangeWidgetBase class exists.
        if (class_exists('Drupal\datetime_range\Plugin\Field\FieldWidget\DateRangeWidgetBase')) {
          // Use the processing from core's Datetime Range.
          $core_range = new DateRangeWidgetBase($this->getPluginId(), $this->getPluginDefinition(), $this->fieldDefinition, $this->getSettings(), $this->thirdPartySettings);
          $values = $core_range->massageFormValues($values, $form, $form_state);
          return $values;
        }
        else {
          // @todo Check for other widgets.
          return $values;
        }
      }
      $timezone = NULL;
      if (!empty($item['timezone'])) {
        $timezone = new \DateTimezone($item['timezone']);
      }
      if (!empty($item['value']) && $item['value'] instanceof DrupalDateTime && $item['end_value'] instanceof DrupalDateTime) {
        if (!$timezone) {
          $value_tz = $item['value']->getTimezone();
          $value_tz_name = $value_tz->getName();
          if ($this->isAllDay(
            $item['value']->getTimestamp(),
            $item['end_value']->getTimestamp(),
            $value_tz_name
          ) && $value_tz_name != $site_tz_name) {
            // Make sure all day events explicitly save timezone if different
            // from the site.
            $timezone = $value_tz;
            $item['timezone'] = $value_tz_name;
          }
        }
        // Adjust the date for storage.
        $item['value'] = $this->smartGetTimestamp($item['value'], $timezone);
      }

      if (!empty($item['end_value']) && $item['end_value'] instanceof DrupalDateTime) {
        // Adjust the date for storage.
        $item['end_value'] = $this->smartGetTimestamp($item['end_value'], $timezone);
      }
      if ($item['duration'] == 'custom') {
        // If using a custom duration, calculate based on start and end times.
        if (!empty($item['end_value']) && !empty($item['value'])) {
          $duration = ((int) $item['end_value'] - (int) $item['value']) / 60;
          $item['duration'] = round($duration);
        }
      }
    }

    if (!$form_state->isValidationComplete()) {
      // Make sure we only process once, after validation.
      return $values;
    }

    // Skip any additional processing if the field doesn't allow recurring.
    $field_def = $this->fieldDefinition;
    if ($field_def instanceof FieldConfigInterface) {
      $allow_recurring = $field_def->getThirdPartySetting('smart_date_recur', 'allow_recurring');
    }
    elseif ($field_def instanceof BaseFieldDefinition) {
      // @todo Document that for custom entities, you must enable recurring
      // functionality by adding ->setSetting('allow_recurring', TRUE)
      // to your field definition.
      $allow_recurring = $field_def->getSetting('allow_recurring');
    }
    else {
      // Not sure what other method we can provide to define this.
      $allow_recurring = FALSE;
    }

    // @phpstan-ignore-next-line
    if ($allow_recurring && \Drupal::hasService('smart_date_recur.manager') && $form_state->getFormObject() instanceof EntityFormInterface) {
      // Provide extra parameters to be stored with the recurrence rule.
      // @phpstan-ignore-next-line
      $month_limit = \Drupal::service('smart_date_recur.manager')->getMonthsLimit($field_def);

      // If form is inline form get entity from it.
      $entity = NULL;
      if (!empty($form['#type']) && $form['#type'] == 'inline_entity_form') {
        $entity = $form['#entity'] ?? NULL;
      }

      if (!($entity instanceof EntityInterface)) {
        $entity = $form_state->getformObject()->getEntity();
      }

      $entity_type = $entity->getEntityTypeId();
      $bundle = $entity->bundle();
      $field_name = $field_def->getName();
      smart_date_recur_generate_rows($values, $entity_type, $bundle, $field_name, $month_limit);
    }

    return $values;
  }

  /**
   * Conditionally convert a DrupalDateTime object to a timestamp.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $time
   *   The time to be converted.
   * @param DateTimezone|null $timezone
   *   An optional timezone to use for conversion.
   */
  private function smartGetTimestamp(DrupalDateTime $time, $timezone = NULL) {
    // Map the date to be relative to a provided timezone, if supplied.
    if ($timezone) {
      $time = $this->remapDatetime($time, $timezone);
    }
    return $time->getTimestamp();
  }

  /**
   * Conditionally convert a DrupalDateTime object to a timestamp.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime|null $time
   *   The time to be converted.
   * @param DateTimezone|null $timezone
   *   An optional timezone to use for conversion.
   */
  public static function remapDatetime($time, $timezone = NULL) {
    if (empty($time)) {
      return '';
    }
    $time = new DrupalDateTime($time->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT), $timezone);
    return $time;
  }

  /**
   * Ensure that the start date <= the end date via #element_validate callback.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validateStartEnd(array &$element, FormStateInterface $form_state, array &$complete_form) {
    if (isset($element['time_wrapper']['value']) && empty($element['value'])) {
      $start_time = $element['time_wrapper']['value']['#value']['object'];
    }
    else {
      $start_time = $element['value']['#value']['object'] ?? NULL;
    }
    if (isset($element['time_wrapper']['end_value']) && empty($element['end_value'])) {
      $end_time = $element['time_wrapper']['end_value']['#value']['object'];
    }
    else {
      $end_time = $element['end_value']['#value']['object'] ?? NULL;
    }

    if ($start_time instanceof DrupalDateTime && $end_time instanceof DrupalDateTime) {
      if ($start_time->getTimestamp() !== $end_time->getTimestamp()) {
        $interval = $start_time->diff($end_time);
        if ($interval->invert === 1) {
          $form_state->setError($element, t('The @title end date cannot be before the start date', ['@title' => $element['#title']]));
        }
      }
    }
  }

  /**
   * Special handling to create form elements for multiple values.
   *
   * Handles generic features for multiple fields:
   * - number of widgets
   * - AHAH-'add more' button
   * - table display and drag-n-drop value reordering.
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition
      ->getName();
    $cardinality = $this->fieldDefinition
      ->getFieldStorageDefinition()
      ->getCardinality();
    $parents = $form['#parents'];

    // Determine the number of widgets to display.
    switch ($cardinality) {
      case FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED:
        $field_state = static::getWidgetState($parents, $field_name, $form_state);
        $max = $field_state['items_count'];
        $is_multiple = TRUE;
        break;

      default:
        $max = $cardinality - 1;
        $is_multiple = $cardinality > 1;
        break;

    }

    // If configured and a default value set, suppress the extra input.
    $field_default = $this->fieldDefinition->getDefaultValueLiteral();
    $default_date_type = $field_default[0]['default_date_type'] ?? '';
    if ($max > 0 && !$this->getSetting('show_extra') && $default_date_type) {
      $max -= 1;
    }

    $title = $this->fieldDefinition
      ->getLabel();
    // @phpstan-ignore-next-line
    $description = FieldFilteredMarkup::create(\Drupal::token()
      ->replace($this->fieldDefinition
        ->getDescription()));
    $elements = [];
    for ($delta = 0; $delta <= $max; $delta++) {

      // Add a new empty item if it doesn't exist yet at this delta.
      if (!isset($items[$delta])) {
        $items
          ->appendItem();
      }

      // For multiple fields, title and description are handled by the wrapping
      // table.
      if ($is_multiple) {
        $element = [
          '#title' => $this
            ->t('@title (value @number)', [
              '@title' => $title,
              '@number' => $delta + 1,
            ]),
          '#title_display' => 'invisible',
          '#description' => '',
        ];
      }
      else {
        $element = [
          '#title' => $title,
          '#title_display' => 'before',
          '#description' => $description,
        ];
      }
      $element = $this
        ->formSingleElement($items, $delta, $element, $form, $form_state);
      if ($element && (!isset($element['#access']) || $element['#access'] !== FALSE)) {

        // Input field for the delta (drag-n-drop reordering).
        if ($is_multiple) {

          // We name the element '_weight' to avoid clashing with elements
          // defined by widget.
          $element['_weight'] = [
            '#type' => 'weight',
            '#title' => $this
              ->t('Weight for row @number', [
                '@number' => $delta + 1,
              ]),
            '#title_display' => 'invisible',
            // Note: this 'delta' is the FAPI #type 'weight' element's property.
            '#delta' => $max,
            '#default_value' => $items[$delta]->_weight ?: $delta,
            '#weight' => 100,
          ];
        }
        $elements[$delta] = $element;
      }
    }
    if ($elements) {
      $elements += [
        '#theme' => 'field_multiple_value_form',
        '#field_name' => $field_name,
        '#cardinality' => $cardinality,
        '#cardinality_multiple' => $this->fieldDefinition
          ->getFieldStorageDefinition()
          ->isMultiple(),
        '#required' => $this->fieldDefinition
          ->isRequired(),
        '#title' => $title,
        '#description' => $description,
        '#max_delta' => $max,
      ];

      // Add 'add more' button, if not working with a programmed form.
      if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED && !$form_state
        ->isProgrammed()) {
        $id_prefix = implode('-', array_merge($parents, [
          $field_name,
        ]));
        $wrapper_id = Html::getUniqueId($id_prefix . '-add-more-wrapper');
        $elements['#prefix'] = '<div id="' . $wrapper_id . '">';
        $elements['#suffix'] = '</div>';
        $elements['add_more'] = [
          '#type' => 'submit',
          '#name' => strtr($id_prefix, '-', '_') . '_add_more',
          '#value' => $this->t('Add another item'),
          '#attributes' => [
            'class' => [
              'field-add-more-submit',
            ],
          ],
          '#limit_validation_errors' => [
            array_merge($parents, [
              $field_name,
            ]),
          ],
          '#submit' => [
            [
              get_class($this),
              'addMoreSubmit',
            ],
          ],
          '#ajax' => [
            'callback' => [
              get_class($this),
              'addMoreAjax',
            ],
            'wrapper' => $wrapper_id,
            'effect' => 'fade',
          ],
        ];
      }
    }
    return $elements;
  }

  /**
   * Creates a default value with the seconds set to zero.
   *
   * @param mixed $date
   *   The configured default.
   * @param string $timezone
   *   A configured timezone for the field, if set.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   A date object for use as a default value in a field widget.
   */
  protected function createNormalizedDefaultValue($date, $timezone) {
    $date = $this->createDefaultValue($date, $timezone);

    // Reset seconds, so they will always fall on :00.
    $date->sub(new \DateInterval('PT' . $date->format('s') . 'S'));

    return $date;
  }

  /**
   * Check if the user-provided value can be resolved to a valid date string.
   *
   * @param string $value
   *   The user-provided input string.
   *
   * @return bool|string
   *   The validated/converted string, or FALSE.
   */
  protected static function validateLimit($value) {
    // If a simple date string, pass validation.
    if (static::validateDate($value)) {
      return $value;
    }
    // Check for a token.
    if (preg_match('|^\[.+(?::.+)+]$|', $value)) {
      $token_service = \Drupal::token();
      $value = $token_service->replace($value, [], ['clear' => TRUE]);
      // Consider a token valid if it returns a date string or empty value.
      if (empty($value) || static::validateDate($value)) {
        return $value;
      }
    }
    return FALSE;
  }

  /**
   * Validate that a date string follows the expected YYYY-MM-DD format.
   *
   * @param string $value
   *   The string to validate.
   *
   * @return bool
   *   Whether or not the string matches the expected format.
   */
  protected static function validateDate($value) {
    // If a simple date string, pass validation.
    if (preg_match('|\d{4}-\d{2}-\d{2}|', $value)) {
      return TRUE;
    }
    return FALSE;
  }

}
