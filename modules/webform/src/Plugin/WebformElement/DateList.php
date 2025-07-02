<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Datetime\DateHelper;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Datetime\Element\Datelist as DatelistElement;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\webform\WebformSubmissionConditionsValidator;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'datelist' element.
 *
 * @WebformElement(
 *   id = "datelist",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Datetime!Element!Datelist.php/class/Datelist",
 *   label = @Translation("Date list"),
 *   description = @Translation("Provides a form element for date & time selection using select menus and text fields."),
 *   category = @Translation("Date/time elements"),
 * )
 */
class DateList extends DateBase implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      'date_min' => '',
      'date_max' => '',
      // Date settings.
      'date_part_order' => [
        'year',
        'month',
        'day',
        'hour',
        'minute',
      ],
      'date_text_parts' => [],
      'date_year_range' => '1900:2050',
      'date_year_range_reverse' => FALSE,
      'date_increment' => 1,
      'date_abbreviate' => TRUE,
    ] + parent::defineDefaultProperties();
  }

  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    // Remove month abbreviation.
    // @see \Drupal\Core\Datetime\Element\Datelist::processDatelist
    if (isset($element['#date_abbreviate']) && $element['#date_abbreviate'] === FALSE) {
      $element['#date_date_callbacks'][] = [DateList::class, 'dateListCallback'];
    }

    // Remove 'for' from the element's label.
    $element['#label_attributes']['webform-remove-for-attribute'] = TRUE;

    $element['#attached']['library'][] = 'webform/webform.element.datelist';
  }

  /**
   * {@inheritdoc}
   */
  protected function getElementSelectorInputsOptions(array $element) {
    $date_parts = $element['#date_part_order'] ?? ['year', 'month', 'day', 'hour', 'minute'];

    $t_args = ['@title' => $this->getAdminLabel($element)];
    $selectors = [
      'day' => (string) $this->t('@title days', $t_args),
      'month' => (string) $this->t('@title months', $t_args),
      'year' => (string) $this->t('@title years', $t_args),
      'hour' => (string) $this->t('@title hours', $t_args),
      'minute' => (string) $this->t('@title minutes', $t_args),
      'second' => (string) $this->t('@title seconds', $t_args),
      'ampm' => (string) $this->t('@title am/pm', $t_args),
    ];

    $selectors = array_intersect_key($selectors, array_combine($date_parts, $date_parts));
    foreach ($selectors as &$selector) {
      $selector .= ' [' . $this->t('Select') . ']';
    }
    return $selectors;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorInputValue($selector, $trigger, array $element, WebformSubmissionInterface $webform_submission) {
    $value = $this->getRawValue($element, $webform_submission);
    if (empty($value)) {
      return NULL;
    }

    // Return date part value.
    // @see \Drupal\Core\Datetime\Element\Datelist::valueCallback
    $input_name = WebformSubmissionConditionsValidator::getSelectorInputName($selector);
    $part = WebformSubmissionConditionsValidator::getInputNameAsArray($input_name, 1);
    switch ($part) {
      case 'day':
        $format = 'j';
        break;

      case 'month':
        $format = 'n';
        break;

      case 'year':
        $format = 'Y';
        break;

      case 'hour':
        $format = in_array('ampm', $element['#date_part_order']) ? 'g' : 'G';
        break;

      case 'minute':
        $format = 'i';
        break;

      case 'second':
        $format = 's';
        break;

      case 'ampm':
        $format = 'a';
        break;

      default:
        $format = '';
    }
    $date = DrupalDateTime::createFromTimestamp(strtotime($value));
    return $date->format($format);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['date']['#title'] = $this->t('Date list settings');
    $form['date']['date_part_order_label'] = [
      '#type' => 'item',
      '#title' => $this->t('Date part and order'),
      '#description' => $this->t("Select the date parts and order that should be used in the element."),
      '#access' => TRUE,
    ];
    $form['date']['date_part_order'] = [
      '#type' => 'webform_tableselect_sort',
      '#header' => ['part' => $this->t('Date part')],
      '#options' => [
        'day' => ['part' => $this->t('Days')],
        'month' => ['part' => $this->t('Months')],
        'year' => ['part' => $this->t('Years')],
        'hour' => ['part' => $this->t('Hours')],
        'minute' => ['part' => $this->t('Minutes')],
        'second' => ['part' => $this->t('Seconds')],
        'ampm' => ['part' => $this->t('AM/PM')],
      ],
    ];
    $form['date']['date_text_parts'] = [
      '#type' => 'checkboxes',
      '#options_display' => 'side_by_side',
      '#title' => $this->t('Date text parts'),
      '#description' => $this->t("Select date parts that should be presented as text fields instead of drop-down selectors."),
      '#options' => [
        'day' => $this->t('Days'),
        'month' => $this->t('Months'),
        'year' => $this->t('Years'),
        'hour' => $this->t('Hours'),
        'minute' => $this->t('Minutes'),
        'second' => $this->t('Seconds'),
      ],
    ];
    $form['date']['date_year_range'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date year range'),
      '#description' => $this->t("A description of the range of years to allow, like '1900:2050', '-3:+3' or '2000:+3', where the first value describes the earliest year and the second the latest year in the range.") . ' ' .
        $this->t('Use min/max validation to define a more specific date range.'),
    ];
    $form['date']['date_year_range_reverse'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Date year range reverse'),
      '#description' => $this->t('If checked date year range will be listed from max to min.'),
      '#return_type' => TRUE,
    ];
    $form['date']['date_increment'] = [
      '#type' => 'number',
      '#title' => $this->t('Date increment'),
      '#description' => $this->t('The increment to use for minutes and seconds'),
      '#min' => 1,
      '#size' => 4,
      '#weight' => 10,
    ];
    $form['date']['date_abbreviate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Abbreviate month'),
      '#description' => $this->t('If checked, month will be abbreviated to three letters.'),
      '#return_value' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    $values = $form_state->getValues();
    $values['date_part_order'] = array_values($values['date_part_order']);
    $values['date_text_parts'] = array_values(array_filter($values['date_text_parts']));
    $form_state->setValues($values);
  }

  /**
   * {@inheritdoc}
   */
  protected function setConfigurationFormDefaultValue(array &$form, array &$element_properties, array &$property_element, $property_name) {
    if (in_array($property_name, ['date_text_parts', 'date_part_order'])) {
      $element_properties[$property_name] = array_combine($element_properties[$property_name], $element_properties[$property_name]);
    }
    parent::setConfigurationFormDefaultValue($form, $element_properties, $property_element, $property_name);
  }

  /**
   * After build handler for Datelist element.
   */
  public static function afterBuild(array $element, FormStateInterface $form_state) {
    $element = parent::afterBuild($element, $form_state);

    // Reverse years from min:max to max:min.
    // @see \Drupal\Core\Datetime\Element\DateElementBase::datetimeRangeYears
    if (!empty($element['#date_year_range_reverse']) && isset($element['year']) && isset($element['year']['#options'])) {
      $options = $element['year']['#options'];
      $element['year']['#options'] = ['' => $options['']] + array_reverse($options, TRUE);
    }
    // Suppress inline error messages for datelist sub-elements.
    foreach (Element::children($element) as $child_key) {
      $element[$child_key]['#error_no_message'] = TRUE;
    }

    // Override Datelist validate callback so that we can support custom
    // #required_error message.
    foreach ($element['#element_validate'] as &$validate_callback) {
      if (is_array($validate_callback) && $validate_callback[0] === 'Drupal\Core\Datetime\Element\Datelist') {
        $validate_callback[0] = DateList::class;
      }
    }

    // Copy the datelist element's states to child inputs.
    if (isset($element['#states'])) {
      foreach (Element::children($element) as $key) {
        $element[$key]['#states'] = $element['#states'];
      }
    }

    return $element;
  }

  /**
   * Override validation callback for a datelist element and set #required_error.
   *
   * @param array $element
   *   The element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validateDatelist(&$element, FormStateInterface $form_state, &$complete_form) {
    $has_required_error = (!empty($element['#required']) && !empty($element['#required_error']));
    if (!$has_required_error) {
      DatelistElement::validateDatelist($element, $form_state, $complete_form);
      return;
    }

    // Clone the $form_state so that we can capture and
    // set #required_error message.
    // Note: We are not using SubformState because we are just trying clone
    // the $form_state.
    $temp_form_state = clone $form_state;

    // Validate the date list element.
    DatelistElement::validateDatelist($element, $temp_form_state, $complete_form);

    // Copy $temp_form_state errors to $form_state error and alter
    // override default required error message is applicable.
    $original_errors = $form_state->getErrors();
    $errors = $temp_form_state->getErrors();
    foreach ($errors as $name => $message) {
      if (empty($original_errors[$name])) {
        if ($message instanceof TranslatableMarkup && $message->getUntranslatedString() === "The %field date is required.") {
          $message = $element['#required_error'];
        }
        $form_state->setErrorByName($name, $message);
      }
    }
  }

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
  public static function dateListCallback(array &$element, FormStateInterface $form_state, $date) {
    $no_abbreviate = (isset($element['#date_abbreviate']) && $element['#date_abbreviate'] === FALSE);
    if ($no_abbreviate && isset($element['month']) && isset($element['month']['#options'])) {
      // Load translated date part labels from the appropriate calendar plugin.
      $date_helper = new DateHelper();
      $element['month']['#options'] = $date_helper->monthNames($element['#required']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return array_merge(['dateListCallback'], parent::trustedCallbacks());
  }

}
