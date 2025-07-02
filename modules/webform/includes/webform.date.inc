<?php

/**
 * @file
 * Callback functions for date, datetime, and time elements.
 */

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\DateList;
use Drupal\webform\Plugin\WebformElement\DateTime;

/**
 * Callback for removing abbreviation from datelist.
 *
 * @param array $element
 *   The element.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The form state.
 * @param \Drupal\Core\Datetime\DrupalDateTime|null $date
 *   The date value.
 *
 * @see \Drupal\Core\Datetime\Element\Datelist::processDatelist
 */
function _webform_datelist_date_date_callback(array &$element, FormStateInterface $form_state, $date) {
  DateList::dateListCallback($element, $form_state, $date);
}

/**
 * Callback for custom datetime date element.
 *
 * @param array $element
 *   The element.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The form state.
 * @param \Drupal\Core\Datetime\DrupalDateTime|null $date
 *   The date value.
 *
 * @see \Drupal\webform\Plugin\WebformElement\DateTime::prepare
 */
function _webform_datetime_date(array &$element, FormStateInterface $form_state, DrupalDateTime $date = NULL) {
  DateTime::dateCallback($element, $form_state, $date);
}

/**
 * Callback for custom datetime time element.
 *
 * @param array $element
 *   The element.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The form state.
 * @param \Drupal\Core\Datetime\DrupalDateTime|null $date
 *   The date value.
 *
 * @see \Drupal\webform\Plugin\WebformElement\DateTime::prepare
 */
function _webform_datetime_time(array &$element, FormStateInterface $form_state, DrupalDateTime $date = NULL) {
  DateTime::timeCallback($element, $form_state, $date);
}
