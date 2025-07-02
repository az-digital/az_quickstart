<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a custom composite element.
 *
 * @WebformElement(
 *   id = "webform_custom_composite",
 *   label = @Translation("Custom composite"),
 *   description = @Translation("Provides a form element to create custom composites using a grid/table layout."),
 *   category = @Translation("Composite elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class WebformCustomComposite extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = [
      'title_display' => '',
      'element' => [],
    ] + $this->defineDefaultMultipleProperties()
      + parent::defineDefaultProperties();
    unset($properties['flexbox']);
    return $properties;
  }

  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultMultipleProperties() {
    $properties = [
      'multiple' => TRUE,
      'multiple__header' => TRUE,
    ] + parent::defineDefaultMultipleProperties();
    return $properties;

  }

  /**
   * {@inheritdoc}
   */
  public function hasMultipleValues(array $element) {
    // WebformComposite extends the WebformMultiple and will always store
    // multiple values.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    // Set cardinality.
    if (isset($element['#multiple'])) {
      $element['#cardinality'] = ($element['#multiple'] === FALSE) ? 1 : $element['#multiple'];
    }

    // Apply multiple properties.
    $multiple_properties = $this->defineDefaultMultipleProperties();
    foreach ($multiple_properties as $multiple_property => $multiple_value) {
      if (strpos($multiple_property, 'multiple__') === 0) {
        $property_name = str_replace('multiple__', '', $multiple_property);
        $element["#$property_name"] = $element["#$multiple_property"] ?? $multiple_value;
      }
    }

    // Default to displaying table header and label.
    $element += ['#header' => TRUE];

    // Transfer '#{composite_key}_{property}' from main element to composite
    // element.
    foreach ($element['#element'] as $composite_key => $composite_element) {
      foreach ($element as $property_key => $property_value) {
        if (strpos($property_key, '#' . $composite_key . '__') === 0) {
          $composite_property_key = str_replace('#' . $composite_key . '__', '#', $property_key);
          $element['#element'][$composite_key][$composite_property_key] = $property_value;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareElementPreRenderCallbacks(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepareElementPreRenderCallbacks($element, $webform_submission);

    // Set custom wrapper type to theme wrappers.
    // @see \Drupal\webform\Element\WebformMultiple::getInfo
    // @see \Drupal\webform\Element\WebformCompositeFormElementTrait::preRenderWebformCompositeFormElement
    if (isset($element['#wrapper_type'])) {
      $element['#theme_wrappers'] = [$element['#wrapper_type']];

      $element += ['#attributes' => []];
      switch ($element['#wrapper_type']) {
        case 'fieldset':
          $element['#attributes']['class'][] = 'fieldgroup';
          $element['#attributes']['class'][] = 'form-composite';
          break;

        case 'container':
          // Apply wrapper attributes to attributes.
          if (isset($element['#wrapper_attributes'])) {
            $element['#attributes'] = NestedArray::mergeDeep($element['#attributes'], $element['#wrapper_attributes']);
          }
          break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareMultipleWrapper(array &$element) {
    // Don't set multiple wrapper since 'webform_composite' extends
    // 'webform_multiple'.
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Always to should multiple element settings since WebformComposite
    // extends WebformMultiple.
    unset($form['multiple']['#states']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildCompositeElementsTable(array $form, FormStateInterface $form_state) {
    return [
      '#type' => 'webform_element_composite',
      '#title' => $this->t('Elements'),
      '#title_display' => 'invisible',
    ];
  }

  /* ************************************************************************ */
  // Preview method.
  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return [
      '#type' => $this->getTypeName(),
      '#title' => $this->getPluginLabel(),
      '#element' => [
        'name' => [
          '#type' => 'textfield',
          '#title' => 'Name',
          '#title_display' => 'invisible',
        ],
        'sex' => [
          '#type' => 'select',
          '#title' => 'Sex',
          '#title_display' => 'invisible',
          '#options' => [
            'Male' => $this->t('Male'),
            'Female' => $this->t('Female'),
          ],
        ],
      ],
    ];
  }

  /* ************************************************************************ */
  // Composite element methods.
  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  public function initializeCompositeElements(array &$element) {
    $element['#webform_composite_elements'] = [];
    foreach ($element['#element'] as $composite_key => $composite_element) {
      $this->elementManager->initializeElement($composite_element);
      // Make sure the composite element has a #admin_title, especially markup.
      $composite_element['#admin_title'] = $composite_element['#admin_title'] ?? $composite_key;
      $element['#webform_composite_elements'][$composite_key] = $composite_element;
    }
    $this->initializeCompositeElementsRecursive($element, $element['#webform_composite_elements']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCompositeElements() {
    // Return empty array since composite (sub) elements are custom.
    return [];
  }

}
