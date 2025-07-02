<?php

namespace Drupal\viewsreference\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;

/**
 * Trait for shared code in Viewsreference Field Widgets.
 */
trait ViewsReferenceTrait {

  /**
   * Build a field element for a viewsreference field.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   Array of default values for this field.
   * @param int $delta
   *   The order of this item in the array of sub-elements (0, 1, 2, etc.).
   * @param array $element
   *   The field item element.
   * @param array $form
   *   The overall form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Array of default values for this field.
   *
   * @return array
   *   The changed field element.
   */
  public function fieldElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();

    // Determine the element parents.
    $field_parents = [];
    if (isset($element['#field_parents'])) {
      $field_parents = $element['#field_parents'];
    }
    elseif (isset($element['target_id']['#field_parents'])) {
      $field_parents = $element['target_id']['#field_parents'];
    }

    $field_path = array_merge($field_parents, [$field_name, $delta]);
    $target_id_field_path = array_merge($field_path, ['target_id']);

    // Get the current values.
    $field_value = $this->itemCurrentValues($items, $delta, $element, $form, $form_state);

    // Setup JavaScript states.
    switch ($element['target_id']['#type']) {
      case 'select':
        $view_selected_js_state = ['!value' => '_none'];
        $ajax_event = 'change';
        $element['target_id']['#default_value'] = $field_value['target_id'] ?? '';
        break;

      default:
        $view_selected_js_state = ['filled' => TRUE];
        $ajax_event = 'viewsreference-select';
        break;
    }

    // Build our target_id field name attribute from the parent elements.
    $target_id_names = $target_id_field_path;
    $target_id_name_string = array_shift($target_id_names);
    foreach ($target_id_names as $target_id_name) {
      $target_id_name_string .= '[' . $target_id_name . ']';
    }

    // We build a unique class name from field elements and any parent elements
    // that might exist which will be used to render the display id options in
    // our ajax function.
    $html_wrapper_id = Html::getUniqueId(implode('-', $field_path));

    $class = get_class($this);

    $element['target_id']['#target_type'] = 'view';
    $element['target_id']['#limit_validation_errors'] = [];
    $element['target_id']['#ajax'] = [
      'callback' => [$class, 'itemAjaxRefresh'],
      'event' => $ajax_event,
      'wrapper' => $html_wrapper_id,
      'progress' => [
        'type' => 'throbber',
        'message' => $this->t('Getting display IDs...'),
      ],
    ];

    $display_id = $field_value['display_id'] ?? NULL;
    $view_name = $field_value['target_id'] ?? NULL;
    $options = [];
    if ($view_name) {

      // An entity reference might be 'Test (view) (test_view),
      // where just 'test_view' should be retrieved.
      $name_parts = explode('(', $view_name);
      $last_part = array_pop($name_parts);
      $last_part = rtrim($last_part, ')');
      $options = $this->getViewDisplays($last_part);
    }

    $element['display_id'] = [
      '#title' => $this->t('Display'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $display_id,
      '#empty_option' => $this->t('- Select -'),
      '#empty_value' => '',
      '#weight' => 10,
      '#attributes' => [
        'class' => [
          'viewsreference-display-id',
        ],
      ],
      '#states' => [
        'visible' => [
          ':input[name="' . $target_id_name_string . '"]' => $view_selected_js_state,
        ],
        'required' => [
          ':input[name="' . $target_id_name_string . '"]' => $view_selected_js_state,
        ],
      ],
      '#ajax' => [
        'callback' => [$class, 'itemAjaxRefresh'],
        'event' => $ajax_event,
        'wrapper' => $html_wrapper_id,
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Getting options...'),
        ],
      ],
    ];

    $field_data = [];
    if (!empty($field_value['data'])) {
      $field_data = unserialize($field_value['data'], ['allowed_classes' => FALSE]);
    }

    $element['options'] = [
      '#type' => 'details',
      '#title' => $this->t('Options'),
      '#weight' => 10,
      '#states' => [
        'visible' => [
          ':input[name="' . $target_id_name_string . '"]' => $view_selected_js_state,
        ],
      ],
    ];

    $viewsreference_plugin_manager = \Drupal::service('plugin.manager.viewsreference.setting');
    $plugin_definitions = $viewsreference_plugin_manager->getDefinitions();
    $enabled_settings = array_filter($this->getFieldSetting('enabled_settings') ?? []);
    foreach ($enabled_settings as $enabled_setting) {
      if (!empty($plugin_definitions[$enabled_setting])) {
        $plugin_definition = $plugin_definitions[$enabled_setting];
        /** @var \Drupal\viewsreference\Plugin\ViewsReferenceSettingInterface $plugin_instance */
        $plugin_instance = $viewsreference_plugin_manager->createInstance($plugin_definition['id'], [
          'view_name' => $view_name,
          'display_id' => $display_id,
        ]);

        $element['options'][$plugin_definition['id']] = [
          '#title' => $plugin_definition['label'],
          '#type' => 'textfield',
          '#default_value' => $field_data[$plugin_definition['id']] ?? $plugin_definition['default_value'],
          '#states' => [
            'visible' => [
              ':input[name="' . $target_id_name_string . '"]' => $view_selected_js_state,
            ],
          ],
        ];

        $plugin_instance->alterFormField($element['options'][$plugin_definition['id']]);
      }
    }

    if (empty($enabled_settings)) {
      unset($element['options']);
    }

    $element['#attached']['library'][] = 'viewsreference/viewsreference';
    $element['#after_build'][] = [$class, 'itemResetValues'];

    // Wrap element for AJAX replacement.
    $element = [
      '#prefix' => '<div id="' . $html_wrapper_id . '">',
      '#suffix' => '</div>',
      // Pass the id along to other methods.
      '#wrapper_id' => $html_wrapper_id,
    ] + $element;

    return $element;
  }

  /**
   * Validate that a display ID is selected for the given View.
   *
   * @param array $field_values
   *   The views reference field values.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $key
   *   The field name.
   */
  public static function validateDisplayId(array $field_values, FormStateInterface $form_state, string $key): void {
    // The select widget nests the target ID which is only later
    // fixed in the massaging of form values.
    if (!empty($field_values[0]['target_id']) && empty($field_values[0]['display_id'])) {
      $message = t('Views Reference Display ID is required.');
      $form_state->setErrorByName($key . '][0][display_id', $message);
    }
  }

  /**
   * Build a field element for a viewsreference field.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   Array of default values for this field.
   * @param int $delta
   *   The order of this item in the array of sub-elements (0, 1, 2, etc.).
   * @param array $element
   *   The field item element.
   * @param array $form
   *   The overall form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Array of default values for this field.
   *
   * @return array
   *   The changed field element.
   */
  public function itemCurrentValues(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $values = [];
    $field_name = $this->fieldDefinition->getName();

    $value_parents = [];
    if (isset($element['#field_parents'])) {
      $value_parents = $element['#field_parents'];
    }
    elseif (isset($element['target_id']['#field_parents'])) {
      $value_parents = $element['target_id']['#field_parents'];
    }
    $value_parents = array_merge($value_parents, [$field_name, $delta]);

    // Get the current value.
    $form_input_values = $form_state->getUserInput();
    $input_value_exists = NULL;
    if ($form_input_values) {
      // User input.
      $input_values = NestedArray::getValue($form_input_values, $value_parents, $input_value_exists);
      if ($input_value_exists) {
        $values = $input_values;
      }
    }

    if (!$input_value_exists) {
      if ($item_state_values = $form_state->getValue($value_parents)) {
        // Stored form state.
        $values = $item_state_values;
      }
      elseif ($item_values = $items[$delta]->getValue()) {
        // Saved items values.
        $values = $item_values;
      }
    }

    return $values;
  }

  /**
   * Clears dependent form values when the view id changes.
   *
   * Implemented as an #after_build callback because #after_build runs before
   * validation, allowing the values to be cleared early enough to prevent the
   * "Illegal choice" error.
   */
  public static function itemResetValues(array $element, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if (!$triggering_element) {
      return $element;
    }

    $keys = [
      'target_id' => [
        'display_id',
      ],
    ];
    $triggering_element_name = end($triggering_element['#parents']);
    if (isset($keys[$triggering_element_name])) {
      $input = &$form_state->getUserInput();
      foreach ($keys[$triggering_element_name] as $key) {
        $parents = array_merge($element['#parents'], [$key]);
        NestedArray::setValue($input, $parents, '');
        $element[$key]['#value'] = '';
      }
    }

    return $element;
  }

  /**
   * Ajax callback to refresh the widget.
   */
  public static function itemAjaxRefresh(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = $triggering_element['#array_parents'];
    array_pop($parents);
    return NestedArray::getValue($form, $parents);
  }

  /**
   * Helper function to get all display ids.
   */
  protected function getAllViewsDisplayIds() {
    $views = Views::getAllViews();
    $options = [];
    foreach ($views as $view) {
      if ($displays = $view->get('display')) {
        foreach ($displays as $display) {
          $options[$display['id']] = $display['display_title'];
        }
      }
    }
    return $options;
  }

  /**
   * Get displays for a particular view.
   *
   * @param string $view_id
   *   The view ID.
   *
   * @return array
   *   An array containing displays for the view.
   */
  protected function getViewDisplays($view_id) {
    $options = [];
    $view_plugins = array_diff($this->getFieldSetting('plugin_types'), ['0']);
    /** @var \Drupal\views\Entity\View $view */
    if ($view = \Drupal::service('entity_type.manager')->getStorage('view')->load($view_id)) {
      if ($displays = $view->get('display')) {
        usort($displays, function ($a, $b) {
          return $a['position'] <=> $b['position'];
        });
        foreach ($displays as $display) {
          // Only add the display to the list when it is enabled. If the key
          // doesn't exist, enabled is assumed. See Views::getApplicableViews.
          $display_enabled = !empty($display['display_options']['enabled']) || !array_key_exists('enabled', $display['display_options']);
          if ($display_enabled && in_array($display['display_plugin'], $view_plugins, TRUE)) {
            $options[$display['id']] = $display['display_title'];
          }
        }
      }
    }
    return $options;
  }

  /**
   * Get view names for a list of view machine names.
   *
   * @param array $views_array
   *   An array containing view machine names.
   *
   * @return array
   *   An array with view labels keyed by machine name.
   */
  protected function getViewNames(array $views_array) {
    $views_list = [];
    foreach ($views_array as $key => $value) {
      if ($view = Views::getView($key)) {
        $views_list[$view->storage->id()] = $view->storage->label();
      }
    }
    return $views_list;
  }

  /**
   * Massages the form values into the format expected for field values.
   *
   * We need to flatten the options array and serialize the settings for the
   * data attribute.
   *
   * @param array $values
   *   The submitted form values produced by the widget.
   * @param array $form
   *   The form structure where field elements are attached to. This might be a
   *   full form structure, or a sub-element of a larger form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   An array of field values, keyed by delta.
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);
    foreach ($values as $key => $value) {
      if (isset($value['options']) && is_array($value['options'])) {
        foreach ($value['options'] as $ind => $option) {
          $values[$key][$ind] = $option;
        }
        unset($value['options']);
      }
    }
    // Serialize settings to store them in the data attribute.
    return $this->serializeSettingsValues($values);
  }

  /**
   * Serialize views reference settings for storage in the data attribute.
   *
   * @param array $values
   *   The submitted form values produced by the widget.
   *
   * @return array
   *   The changed values with a serialized data attribute.
   */
  protected function serializeSettingsValues(array $values) {
    $viewsreference_plugin_manager = \Drupal::service('plugin.manager.viewsreference.setting');
    $plugin_definitions = $viewsreference_plugin_manager->getDefinitions();
    foreach ($values as $delta => $value) {
      $serialized_fields = [];
      foreach ($plugin_definitions as $plugin_definition) {
        $serialized_fields[$plugin_definition['id']] = $value[$plugin_definition['id']] ?? NULL;
        unset($values[$delta][$plugin_definition['id']]);
      }
      $values[$delta]['data'] = serialize($serialized_fields);
    }
    return $values;
  }

}
