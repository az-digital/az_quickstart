<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\Utility\WebformOptionsHelper;
use Drupal\webform\WebformSubmissionConditionsValidator;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'checkboxes' element.
 *
 * @WebformElement(
 *   id = "checkboxes",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Checkboxes.php/class/Checkboxes",
 *   label = @Translation("Checkboxes"),
 *   description = @Translation("Provides a form element for a set of checkboxes."),
 *   category = @Translation("Options elements"),
 * )
 */
class Checkboxes extends OptionsBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      'multiple' => TRUE,
      'multiple_error' => '',
      // Options settings.
      'options_display' => 'one_column',
      'options_description_display' => 'description',
      'options__properties' => [],
      // Options all and none.
      'options_all' => FALSE,
      'options_all_value' => 'all',
      'options_all_text' => (string) $this->t('All of the above'),
      'options_none' => FALSE,
      'options_none_value' => 'none',
      'options_none_text' => (string) $this->t('None of the above'),
      // Wrapper.
      'wrapper_type' => 'fieldset',
    ] + parent::defineDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  protected function defineTranslatableProperties() {
    return array_merge(parent::defineTranslatableProperties(), ['options_all_text', 'options_none_text']);
  }

  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  public function supportsMultipleValues() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasMultipleValues(array $element) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function initialize(array &$element) {
    parent::initialize($element);

    $option_types = ['options_all', 'options_none'];
    foreach ($option_types as $option_type) {
      if (!empty($element['#' . $option_type])) {
        $element['#' . $option_type . '_value'] = $this->getElementProperty($element, $option_type . '_value');
        $element['#' . $option_type . '_text'] = $this->getElementProperty($element, $option_type . '_text');
        // Set #options for every element except 'webform_entity_checkboxes'.
        // @see \Drupal\webform\Plugin\WebformElement\WebformEntityReferenceTrait::setOptions
        if ($element['#type'] !== 'webform_entity_checkboxes') {
          $element['#options'][$element['#' . $option_type . '_value']] = $element['#' . $option_type . '_text'];
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    // Set 'data-options-all' and 'data-options-all' attribute.
    $option_types = ['options_all', 'options_none'];
    foreach ($option_types as $option_type) {
      if (!empty($element['#' . $option_type])) {
        // If options are randomized, make sure the 'all' and 'none' checkboxes
        // are always last.
        if (!empty($element['#options_randomize'])) {
          unset($element['#options'][$element['#' . $option_type . '_value']]);
          $element['#options'][$element['#' . $option_type . '_value']] = $element['#' . $option_type . '_text'];
        }
        $element['#wrapper_attributes']['data-' . str_replace('_', '-', $option_type)] = $element['#' . $option_type . '_value'];
      }
    }

    $element['#attached']['library'][] = 'webform/webform.element.checkboxes';

    if (!empty($element['#options_all']) || !empty($element['#options_none'])) {
      $element['#element_validate'][] = [get_class($this), 'validateCheckAllOrNone'];
    }
  }

  /**
   * Form API callback. Handle check all or none option.
   */
  public static function validateCheckAllOrNone(array &$element, FormStateInterface $form_state, array &$completed_form) {
    $values = $form_state->getValue($element['#parents'], []);
    if (!empty($element['#options_all'])) {
      // Remove options all value.
      WebformArrayHelper::removeValue($values, $element['#options_all_value']);
    }
    elseif (!empty($element['#options_none']) && in_array($element['#options_none_value'], $values)) {
      // Only allow none option to be submitted.
      $values = [$element['#options_none_value']];
    }
    $form_state->setValueForElement($element, $values);
  }

  /**
   * {@inheritdoc}
   */
  protected function getElementSelectorInputsOptions(array $element) {
    $selectors = $element['#options'];
    foreach ($selectors as $index => $text) {
      // Remove description from text.
      [$text] = WebformOptionsHelper::splitOption($text);
      // Append element type to text.
      $text .= ' [' . $this->t('Checkbox') . ']';
      $selectors[$index] = $text;
    }
    return $selectors;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorInputValue($selector, $trigger, array $element, WebformSubmissionInterface $webform_submission) {
    $input_name = WebformSubmissionConditionsValidator::getSelectorInputName($selector);
    $option_value = WebformSubmissionConditionsValidator::getInputNameAsArray($input_name, 1);
    $value = $this->getRawValue($element, $webform_submission) ?: [];
    if (in_array($option_value, $value, TRUE)) {
      return (in_array($trigger, ['checked', 'unchecked'])) ? TRUE : $value;
    }
    else {
      return (in_array($trigger, ['checked', 'unchecked'])) ? FALSE : NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorSourceValues(array $element) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Checkboxes must require > 2 options.
    $form['element']['multiple']['#min'] = 2;

    // Include options all and none.
    $option_types = [
      'options_all' => $this->t('All'),
      'options_none' => $this->t('None'),
    ];
    foreach ($option_types as $option_type => $option_label) {
      if (!$this->hasProperty($option_type)) {
        continue;
      }

      $t_args = ['@type' => $option_label];
      $form['options'][$option_type] = [
        '#type' => 'checkbox',
        '#title' => $this->t("Include '@type of the above' option", $t_args),
        '#return_value' => TRUE,
      ];
      $form['options'][$option_type . '_container'] = $this->getFormInlineContainer() + [
        '#states' => [
          'visible' => [[':input[name="properties[' . $option_type . ']"]' => ['checked' => TRUE]]],
          'required' => [[':input[name="properties[' . $option_type . ']"]' => ['checked' => TRUE]]],
        ],
      ];
      $form['options'][$option_type . '_container']['#attributes']['data-webform-states-no-clear'] = TRUE;
      $form['options'][$option_type . '_container'][$option_type . '_value'] = [
        '#type' => 'textfield',
        '#title' => $this->t("@type option value", $t_args),
      ];
      $form['options'][$option_type . '_container'][$option_type . '_text'] = [
        '#type' => 'textfield',
        '#title' => $this->t("@type option text", $t_args),
        '#attributes' => ['class' => ['webform-ui-element-form-inline--input-double-width']],
      ];
    }
    return $form;
  }

}
