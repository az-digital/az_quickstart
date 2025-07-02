<?php

namespace Drupal\smart_date\Plugin\Field\FieldType;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeFieldItemList;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\smart_date\SmartDateDurationConfigTrait;

/**
 * Represents a configurable entity smartdate field.
 */
class SmartDateFieldItemList extends DateTimeFieldItemList {

  use SmartDateDurationConfigTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultValuesForm(array &$form, FormStateInterface $form_state) {
    if (empty($this->getFieldDefinition()->getDefaultValueCallback())) {
      if ($this->getFieldDefinition()->getDefaultValueLiteral()) {
        $default_value = $this->getFieldDefinition()->getDefaultValueLiteral()[0];
      }
      else {
        $default_value = [];
      }

      $element = parent::defaultValuesForm($form, $form_state);

      $element['default_date_type']['#options']['next_hour'] = $this->t('Next hour');

      unset($element['default_time_type']);

      $this->addDurationConfig($element, $default_value);

      return $element;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormValidate(array $element, array &$form, FormStateInterface $form_state) {
    $duration = $form_state->getValue([
      'default_value_input',
      'default_duration',
    ]) ?? '';
    if ($duration) {
      $increments = SmartDateListItemBase::parseValues($form_state->getValue([
        'default_value_input',
        'default_duration_increments',
      ]));
      // Handle a false result: will display the proper error later.
      if (!$increments) {
        $increments = [];
      }
      $increment_min = -1;
      // Iterate through returned array and throw an error for an invalid key.
      foreach ($increments as $key => $label) {
        if (intval($key) == 0 && $key !== '0' && $key !== 0 && $key !== 'custom') {
          $form_state->setErrorByName('default_value_input][default_duration_increments', $this->t('Invalid tokens in the allowed increments specified. Please provide either integers or "custom" as the key for each value.'));
          break;
        }
        else {
          $increment_min = ($increment_min < intval($key)) ? intval($key) : $increment_min;
        }
      }
      if (!in_array('custom', $increments)) {
        if ($increment_min < 0) {
          $form_state->setErrorByName('default_value_input][default_duration_increments', $this->t('Unable to parse valid durations from the allowed increments specified.'));
        }
        else {
          $messenger = \Drupal::messenger();
          $messenger->addMessage($this->t('No string to allow for custom values, so the provided increments will be strictly enforced.'), 'warning');
        }
      }
      if (!isset($increments[$duration])) {
        $form_state->setErrorByName('default_value_input][default_duration', $this->t('Please specify a default duration that is one of the provided options.'));
      }
    }
    // Validate that if min and max are both set max >= min.
    $limits_to_check = ['min', 'max'];
    $limits = [];
    foreach ($limits_to_check as $check) {
      $user_val = $form_state->getValue([
        'default_value_input',
        $check,
      ]) ?? '';
      if (empty($user_val)) {
        continue;
      }
      $user_val = $this->validateLimit($user_val);
      if ($user_val !== FALSE) {
        $limits[$check] = $user_val;
      }
      else {
        $form_state->setErrorByName('default_value_input][' . $check, $this->t('Invalid limit value provided. Please use either a date string in the format or YYYY-MM-DD or token.'));
      }
    }
    if (count($limits) == 2) {
      if ($limits['min'] > $limits['max']) {
        $form_state->setErrorByName('default_value_input][min', $this->t('The maximum date limit cannot be before the minimum.'));
      }
    }
    // Use the parent class method to validate relative dates.
    DateTimeFieldItemList::defaultValuesFormValidate($element, $form, $form_state);
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

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormSubmit(array $element, array &$form, FormStateInterface $form_state) {
    $duration = $form_state->getValue([
      'default_value_input',
      'default_duration',
    ]) ?? '';
    $duration_increments = $form_state->getValue([
      'default_value_input',
      'default_duration_increments',
    ]) ?? '';
    if (strlen((string) $duration) && strlen((string) $duration_increments)) {
      if ($duration) {
        $form_state->setValueForElement($element['default_duration'], $duration);
      }
      if ($duration_increments) {
        $form_state->setValueForElement($element['default_duration_increments'], $duration_increments);
      }
      return [$form_state->getValue('default_value_input')];
    }
    // Use the parent class method to store current date configuration.
    DateTimeFieldItemList::defaultValuesFormSubmit($element, $form, $form_state);
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function processDefaultValue($default_value, FieldableEntityInterface $entity, FieldDefinitionInterface $definition) {
    // Explicitly call the base class so that we can get the default value
    // types.
    $default_value = FieldItemList::processDefaultValue($default_value, $entity, $definition);

    // No default set, so nothing to do.
    if (empty($default_value[0]['default_date_type'])) {
      return $default_value;
    }

    // A default date+time value should be in the format and timezone used
    // for date storage.
    $date = new DrupalDateTime($default_value[0]['default_date'], DateTimeItemInterface::STORAGE_TIMEZONE);

    // If using 'next_hour' for 'default_date_type', do custom processing.
    if ($default_value[0]['default_date_type'] == 'next_hour') {
      $date->modify('+1 hour');
      // After conversion to timestamp, we round up, so offset for this.
      $min = (int) $date->format('i') + 1;
      $date->modify('-' . $min . ' minutes');
      // If min or max values set, apply to default value.
      $limits_to_check = ['min', 'max'];
      $limits = [];
      foreach ($limits_to_check as $check) {
        if (!empty($default_value[0][$check]) && $limit = static::validateLimit($default_value[0][$check])) {
          $limits[$check] = new DrupalDateTime($limit, DateTimeItemInterface::STORAGE_TIMEZONE);
        }
      }
      if (!empty($limits['min']) && $date < $limits['min']) {
        $date->setDate($limits['min']->format('Y'), $limits['min']->format('n'), $limits['min']->format('j'));
      }
      elseif (!empty($limits['max']) && $date > $limits['max']) {
        $date->setDate($limits['max']->format('Y'), $limits['max']->format('n'), $limits['max']->format('j'));
      }
    }

    $value = $date->getTimestamp();
    // Round up to the next minute.
    $second = $date->format("s");
    if ($second > 0) {
      $value += 60 - $second;
    }
    // Calculate the end value.
    $duration = (int) $default_value[0]['default_duration'];
    $end_value = $value + ($duration * 60);

    $default_value = [
      [
        'value' => $value,
        'end_value' => $end_value,
        'date' => $date,
      ],
    ];

    return $default_value;
  }

}
