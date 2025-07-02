<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformFormHelper;
use Drupal\webform\Utility\WebformOptionsHelper;

/**
 * Provides a mapping element.
 *
 * @FormElement("webform_mapping")
 */
class WebformMapping extends FormElement {

  /**
   * Require all.
   */
  const REQUIRED_ALL = 'all';

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processWebformMapping'],
        [$class, 'processAjaxForm'],
      ],
      '#theme_wrappers' => ['form_element'],
      '#filter' => TRUE,
      '#required' => FALSE,
      '#source' => [],
      '#source__description_display' => 'description',
      '#destination' => [],
      '#arrow' => '→',
    ];
  }

  /**
   * Processes a likert scale webform element.
   */
  public static function processWebformMapping(&$element, FormStateInterface $form_state, &$complete_form) {
    // Set translated default properties.
    $element += [
      '#source__title' => t('Source'),
      '#destination__title' => t('Destination'),
      '#arrow' => '→',
    ];

    $arrow = htmlentities($element['#arrow']);

    // Process sources.
    $sources = [];
    foreach ($element['#source'] as $source_key => $source) {
      $source = (string) $source;
      if (!WebformOptionsHelper::hasOptionDescription($source)) {
        $source_description_property_name = NULL;
        $source_title = $source;
        $source_description = '';
      }
      else {
        $source_description_property_name = ($element['#source__description_display'] === 'help') ? 'help' : 'description';
        [$source_title, $source_description] = WebformOptionsHelper::splitOption($source);
      }
      $sources[$source_key] = [
        'description_property_name' => $source_description_property_name,
        'title' => $source_title,
        'description' => $source_description,
      ];
    }

    // Setup destination__type depending if #destination is defined.
    if (empty($element['#destination__type'])) {
      $element['#destination__type'] = (empty($element['#destination'])) ? 'textfield' : 'select';
    }

    // Set base destination element.
    $destination_element_base = [
      '#title_display' => 'invisible',
      '#required' => ($element['#required'] === static::REQUIRED_ALL) ? TRUE : FALSE,
      '#error_no_message'  => ($element['#required'] !== static::REQUIRED_ALL) ? TRUE : FALSE,
    ];

    // Get base #destination__* properties.
    foreach ($element as $element_key => $element_value) {
      if (strpos($element_key, '#destination__') === 0 && !in_array($element_key, ['#destination__title'])) {
        $destination_element_base[str_replace('#destination__', '#', $element_key)] = $element_value;
      }
    }

    // Build header.
    $header = [
      ['data' => ['#markup' => $element['#source__title'] . ' ' . $arrow]],
      ['data' => ['#markup' => $element['#destination__title']]],
    ];

    // Build rows.
    $rows = [];
    foreach ($sources as $source_key => $source) {
      $default_value = $element['#default_value'][$source_key] ?? NULL;

      // Source element.
      $source_element = ['data' => []];
      $source_element['data']['title'] = ['#markup' => $source['title']];
      if ($source['description_property_name'] === 'help') {
        $source_element['data']['help'] = [
          '#type' => 'webform_help',
          '#help' => $source['description'],
          '#help_title' => $source['title'],
        ];
      }
      $source_element['data']['arrow'] = ['#markup' => $arrow, '#prefix' => ' '];
      if ($source['description_property_name'] === 'description') {
        $source_element['data']['description'] = [
          '#type' => 'container',
          '#markup' => $source['description'],
          '#attributes' => ['class' => ['description']],
        ];
      }

      // Destination element.
      $destination_element = $destination_element_base + [
        '#title' => $source['title'],
        '#required' => $element['#required'],
        '#default_value' => $default_value,
      ];

      // Apply #parents to destination element.
      if (isset($element['#parents'])) {
        $destination_element['#parents'] = array_merge($element['#parents'], [$source_key]);
      }

      switch ($element['#destination__type']) {
        case 'select':
        case 'webform_select_other':
          $destination_element += [
            '#empty_option' => t('- Select -'),
            '#options' => $element['#destination'],
          ];
          break;
      }

      // Add row.
      $rows[$source_key] = [
        'source' => $source_element,
        $source_key => $destination_element,
      ];
    }

    $element['table'] = [
      '#tree' => TRUE,
      '#type' => 'table',
      '#header' => $header,
      '#attributes' => [
        'class' => ['webform-mapping-table'],
      ],
    ] + $rows;

    // Build table element with selected properties.
    $properties = [
      '#states',
      '#sticky',
    ];
    $element['table'] += array_intersect_key($element, array_combine($properties, $properties));

    // Add validate callback.
    $element += ['#element_validate' => []];
    array_unshift($element['#element_validate'], [get_called_class(), 'validateWebformMapping']);

    if (!empty($element['#states'])) {
      WebformFormHelper::processStates($element, '#wrapper_attributes');
    }

    $element['#attached']['library'][] = 'webform/webform.element.mapping';

    return $element;
  }

  /**
   * Validates a mapping element.
   */
  public static function validateWebformMapping(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = NestedArray::getValue($form_state->getValues(), $element['#parents']);

    // Filter values.
    if ($element['#filter']) {
      $value = array_filter($value);
    }

    // Note: Not validating REQUIRED_ALL because each destination element is
    // already required.
    if (Element::isVisibleElement($element)
      && $element['#required']
      && $element['#required'] !== static::REQUIRED_ALL
      && empty($value)) {
      WebformElementHelper::setRequiredError($element, $form_state);
    }

    $element['#value'] = $value;
    $form_state->setValueForElement($element, $value);
  }

}
