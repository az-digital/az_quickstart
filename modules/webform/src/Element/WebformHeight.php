<?php

namespace Drupal\webform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Provides a webform height element.
 *
 * @FormElement("webform_height")
 */
class WebformHeight extends FormElement {

  use WebformCompositeFormElementTrait;

  /**
   * Denotes height symbol.
   *
   * @var string
   */
  const HEIGHT_SYMBOL = 'symbol';

  /**
   * Denotes height abbreviate.
   *
   * @var string
   */
  const HEIGHT_ABBREVIATE = 'abbreviate';

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processWebformHeight'],
      ],
      '#pre_render' => [
        [$class, 'preRenderWebformCompositeFormElement'],
      ],
      '#required' => FALSE,
      '#height_type' => 'number',
      '#height_format' => '',
      '#feet__min' => 0,
      '#feet__max' => 8,
      '#inches__step' => 1,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      if (!isset($element['#default_value']) || $element['#default_value'] === '') {
        $element['#default_value'] = ['feet' => NULL, 'inches' => NULL];
      }
      else {
        $value = (float) $element['#default_value'];
        $feet = floor($value / 12);
        $inches = $value - ($feet * 12);
        $element['#default_value'] = ['feet' => $feet, 'inches' => $inches];
      }
      return $element['#default_value'];
    }
    else {
      $element['#default_value'] = $input;
      return $input;
    }
  }

  /**
   * Display feet and inches for height element.
   */
  public static function processWebformHeight(&$element, FormStateInterface $form_state, &$complete_form) {
    switch ($element['#height_format']) {
      case static::HEIGHT_SYMBOL:
        $feet_plural = '″';
        $feet_singular = '″';
        $inches_plural = '′';
        $inches_singular = '′';
        break;

      case static::HEIGHT_ABBREVIATE:
        $feet_plural = t('ft', [], ['context' => 'Imperial height unit abbreviate']);
        $feet_singular = t('ft', [], ['context' => 'Imperial height unit abbreviate']);
        $inches_plural = t('in', [], ['context' => 'Imperial height unit abbreviate']);
        $inches_singular = t('in', [], ['context' => 'Imperial height unit abbreviate']);
        break;

      default:
        $feet_plural = t('feet', [], ['context' => 'Imperial height unit']);
        $feet_singular = t('foot', [], ['context' => 'Imperial height unit']);
        $inches_plural = t('inches', [], ['context' => 'Imperial height unit']);
        $inches_singular = t('inch', [], ['context' => 'Imperial height unit']);
        break;
    }

    $select_element_defaults = [
      '#type' => 'select',
      '#empty_option' => '',
    ];

    $element['#tree'] = TRUE;

    // Feet options.
    $feet_range = range($element['#feet__min'], $element['#feet__max']);
    $feet_options = array_combine($feet_range, $feet_range);

    // Inches options.
    $inches_step = $element['#inches__step'];
    $inches_range = range(0, 11, $inches_step);
    if ($inches_step !== 1 && floor($inches_step) !== $inches_step) {
      $decimals = strlen(substr(strrchr($inches_step, '.'), 1));
      $inches_range = array_map(function ($number) use ($decimals) {
        return number_format($number, $decimals);
      }, $inches_range);
    }
    $inches_options = array_combine($inches_range, $inches_range);

    // Container.
    $element['container'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form--inline', 'clearfix']],
    ];

    $t_args = [
      '@title' => $element['#title'],
      '@unit' => t('Feet', [], ['context' => 'Imperial height unit']),
    ];
    $element['container']['feet'] = [
      '#title' => t('@title: @unit', $t_args),
      '#title_display' => 'invisible',
      '#required' => $element['#required'],
      '#error_no_message' => TRUE,
      '#default_value' => $element['#default_value']['feet'],
      '#parents' => array_merge($element['#parents'], ['feet']),
    ];
    $t_args = [
      '@title' => $element['#title'],
      '@unit' => t('Inches', [], ['context' => 'Imperial height unit']),
    ];
    $element['container']['inches'] = [
      '#title' => t('@title: @unit', $t_args),
      '#title_display' => 'invisible',
      '#field_prefix' => ' ',
      '#required' => $element['#required'],
      '#error_no_message' => TRUE,
      '#default_value' => $element['#default_value']['inches'],
      '#parents' => array_merge($element['#parents'], ['inches']),
    ];

    switch ($element['#height_type']) {
      case 'select':
        $element['container']['feet'] += $select_element_defaults + [
          '#field_suffix' => $feet_plural,
          '#options' => $feet_options,
        ];
        $element['container']['inches'] += $select_element_defaults + [
          '#field_suffix' => $inches_plural,
          '#options' => $inches_options,
        ];
        break;

      case 'select_suffix':
        foreach ($feet_options as $option_value => $option_text) {
          $feet_options[$option_value] .= ' ' . ($option_value === 1 ? $feet_singular : $feet_plural);
        }
        foreach ($inches_options as $option_value => $option_text) {
          $inches_options[$option_value] .= ' ' . ($option_value === 1 ? $inches_singular : $inches_plural);
        }
        $element['container']['feet'] += $select_element_defaults + [
          '#options' => $feet_options,
        ];
        $element['container']['inches'] += $select_element_defaults + [
          '#options' => $inches_options,
        ];
        break;

      default:
        $element['container']['feet'] += [
          '#type' => 'number',
          '#field_suffix' => $feet_plural,
          '#min' => $element['#feet__min'],
          '#max' => $element['#feet__max'],
          '#step' => 1,
        ];
        $element['container']['inches'] += [
          '#type' => 'number',
          '#field_suffix' => $inches_plural,
          '#min' => 0,
          '#max' => 11,
          '#step' => $inches_step,
        ];
        break;
    }

    // Apply custom properties to feet and inches elements.
    foreach ($element as $key => $value) {
      if (strpos($key, '__') !== FALSE) {
        [$element_key, $property_key] = explode('__', ltrim($key, '#'));
        if (isset($element['container'][$element_key])) {
          $element['container'][$element_key]["#$property_key"] = $value;
        }
      }
    }

    // Initialize the feet and inches elements to allow
    // for webform enhancements.
    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');
    $element_manager->buildElement($element['container']['feet'], $complete_form, $form_state);
    $element_manager->buildElement($element['container']['inches'], $complete_form, $form_state);

    // Add validate callback.
    $element += ['#element_validate' => []];
    array_unshift($element['#element_validate'], [
      get_called_class(),
      'validateWebformHeight',
    ]);

    return $element;
  }

  /**
   * Validates an height element.
   */
  public static function validateWebformHeight(&$element, FormStateInterface $form_state, &$complete_form) {
    $height_element = &$element['container'];

    if ($height_element['feet']['#value'] === '' && $height_element['inches']['#value'] === '') {
      $value = '';
    }
    else {
      $feet = (float) $height_element['feet']['#value'];
      $inches = (float) $height_element['inches']['#value'];
      $value = ($feet * 12) + $inches;
    }

    if (Element::isVisibleElement($element) && $element['#required'] && empty($value)) {
      WebformElementHelper::setRequiredError($element, $form_state);
    }

    $form_state->setValueForElement($height_element['feet'], NULL);
    $form_state->setValueForElement($height_element['inches'], NULL);

    $value = (string) $value;
    $element['#value'] = $value;
    $form_state->setValueForElement($element, $value);
  }

}
