<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformHeight as WebformHeightElement;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionConditionsValidator;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'height' element.
 *
 * @WebformElement(
 *   id = "webform_height",
 *   label = @Translation("Height (feet/inches)"),
 *   description = @Translation("Provides a form element to collect height in feet and inches."),
 *   category = @Translation("Advanced elements"),
 * )
 */
class WebformHeight extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      'height_type' => 'number',
      'height_format' => '',
      'feet__min' => 0,
      'feet__max' => 8,
    ] + parent::defineDefaultProperties() + $this->defineDefaultMultipleProperties();
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);
    $format = $this->getItemFormat($element);

    if ($format === 'raw') {
      return $value;
    }

    if (empty($value)) {
      return '';
    }

    $value = (float) $value;
    $feet = floor($value / 12);
    $inches = $value - ($feet * 12);

    $height_format = $this->getElementProperty($element, 'height_format');
    switch ($height_format) {
      case WebformHeightElement::HEIGHT_SYMBOL:
        $feet_plural = '″';
        $feet_singular = '″';
        $inches_plural = '′';
        $inches_singular = '′';
        break;

      case WebformHeightElement::HEIGHT_ABBREVIATE:
        $feet_plural = ' ' . $this->t('ft', [], ['context' => 'Imperial height unit abbreviate']);
        $feet_singular = ' ' . $this->t('ft', [], ['context' => 'Imperial height unit abbreviate']);
        $inches_plural = ' ' . $this->t('in', [], ['context' => 'Imperial height unit abbreviate']);
        $inches_singular = ' ' . $this->t('in', [], ['context' => 'Imperial height unit abbreviate']);
        break;

      default:
        $feet_plural = ' ' . $this->t('feet', [], ['context' => 'Imperial height unit']);
        $feet_singular = ' ' . $this->t('foot', [], ['context' => 'Imperial height unit']);
        $inches_plural = ' ' . $this->t('inches', [], ['context' => 'Imperial height unit']);
        $inches_singular = ' ' . $this->t('inch', [], ['context' => 'Imperial height unit']);
        break;
    }

    return ($feet ? $feet . ($feet === 1 ? $feet_singular : $feet_plural) : '')
      . ($height_format !== WebformHeightElement::HEIGHT_SYMBOL && $feet && $inches ? ' ' : '')
      . ($inches ? $inches . ($inches === 1 ? $inches_singular : $inches_plural) : '');
  }

  /**
   * {@inheritdoc}
   */
  public function hasValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);
    return ($value === '0') ? FALSE : parent::hasValue($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    return rand(30, 60);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['height'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Height settings'),
    ];
    $form['height']['height_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Height element type'),
      '#options' => [
        'number' => $this->t('Number input'),
        'select' => $this->t('Select menu'),
        'select_suffix' => $this->t('Select menu with suffixes'),
      ],
    ];
    $form['height']['height_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Height suffix format'),
      '#options' => [
        '' => $this->t('Units (feet/foot and inches/inch)'),
        WebformHeightElement::HEIGHT_ABBREVIATE => $this->t('Abbreviated units (ft and in)'),
        WebformHeightElement::HEIGHT_SYMBOL => $this->t('Symbols (″ and ′)'),
      ],
    ];
    $form['height']['feet_container'] = $this->getFormInlineContainer();
    $form['height']['feet_container']['feet__min'] = [
      '#type' => 'number',
      '#title' => $this->t('Feet minimum'),
      '#description' => $this->t("Specifies the feet's minimum value."),
      '#step' => 'any',
      '#size' => 4,
    ];
    $form['height']['feet_container']['feet__max'] = [
      '#type' => 'number',
      '#title' => $this->t('Feet maximum'),
      '#description' => $this->t("Specifies the feet's maximum value."),
      '#step' => 'any',
      '#size' => 4,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getElementSelectorInputsOptions(array $element) {
    $t_args = ['@title' => $this->getAdminLabel($element)];
    return [
      'feet' => (string) $this->t('@title: Feet', $t_args),
      'inches' => (string) $this->t('@title: Inches', $t_args),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorInputValue($selector, $trigger, array $element, WebformSubmissionInterface $webform_submission) {
    $value = $this->getRawValue($element, $webform_submission);
    if (empty($value)) {
      return NULL;
    }

    $input_name = WebformSubmissionConditionsValidator::getSelectorInputName($selector);
    $part = WebformSubmissionConditionsValidator::getInputNameAsArray($input_name, 1);

    $value = (float) $value;
    $feet = floor($value / 12);
    $inches = $value - ($feet * 12);

    switch ($part) {
      case 'feet':
        return $feet;

      case 'inches':
        return $inches;

      default:
        return NULL;
    }
  }

}
