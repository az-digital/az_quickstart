<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'date' element.
 *
 * @WebformElement(
 *   id = "date",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Date.php/class/Date",
 *   label = @Translation("Date"),
 *   description = @Translation("Provides a form element for date selection."),
 *   category = @Translation("Date/time elements"),
 * )
 */
class Date extends DateBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $date_format = '';
    // Date formats cannot be loaded during install or update.
    if (!defined('MAINTENANCE_MODE')) {
      /** @var \Drupal\Core\Datetime\DateFormatInterface $date_format_entity */
      if ($date_format_entity = DateFormat::load('html_date')) {
        $date_format = $date_format_entity->getPattern();
      }
    }

    $properties = [
      // Date settings.
      'date_date_format' => $date_format,
      'placeholder' => '',
      'step' => '',
      'size' => '',
    ] + parent::defineDefaultProperties();
    return $properties;
  }

  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    // Unset custom date format which is only used by the
    // webform_jqueryui_datepicker.module.
    $has_datepicker = isset($element['#datepicker']) && $this->moduleHandler->moduleExists('webform_jqueryui_datepicker');
    if (isset($element['#date_date_format']) && !$has_datepicker) {
      unset($element['#date_date_format']);
    }

    // Set default date format to HTML date.
    $element['#date_date_format'] = $element['#date_date_format'] ?? $this->getDefaultProperty('date_date_format');

    // Set placeholder attribute.
    if (!empty($element['#placeholder'])) {
      $element['#attributes']['placeholder'] = $element['#placeholder'];
    }

    // Prepare element after date format has been updated.
    parent::prepare($element, $webform_submission);

    // Set the (input) type attribute to 'date'.
    // @see \Drupal\Core\Render\Element\Date::getInfo
    $element['#attributes']['type'] = 'date';
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultValue(array &$element) {
    parent::setDefaultValue($element);

    if (function_exists('_webform_jqueryui_datepicker_set_default_value')) {
      _webform_jqueryui_datepicker_set_default_value($element);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormat(array $element) {
    $format = parent::getItemFormat($element);
    // Drupal's default date fallback includes the time so we need to fallback
    // to the specified or default date only format.
    if ($format === 'fallback') {
      $format = $element['#date_date_format'] ?? $this->getDefaultProperty('date_date_format');
    }
    return $format;
  }

}
