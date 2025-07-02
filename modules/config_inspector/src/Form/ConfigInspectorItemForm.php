<?php

namespace Drupal\config_inspector\Form;

use Drupal\Core\Config\Schema\ArrayElement;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\Plugin\DataType\BooleanData;
use Drupal\Core\TypedData\Plugin\DataType\IntegerData;
use Drupal\Core\TypedData\Plugin\DataType\StringData;

/**
 * Defines a form for editing configuration translations.
 */
class ConfigInspectorItemForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_inspector_item_form';
  }

  /**
   * Build configuration form with metadata and values.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $schema = NULL) {
    $form['structure'] = $this->buildFormConfigElement($schema);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Format config schema as a tree.
   */
  protected function buildFormConfigElement($schema, $collapsed = FALSE) {
    $build = [];
    foreach ($schema as $key => $element) {
      $definition = $element->getDataDefinition();
      $label = $definition['label'] ?: $this->t('N/A');
      if ($element instanceof ArrayElement) {
        $build[$key] = [
          '#type' => 'details',
          '#title' => $label,
          '#open' => !$collapsed,
        ] + $this->buildFormConfigElement($element, TRUE);
      }
      else {
        $class = $definition['class'];
        switch ($class) {
          case BooleanData::class:
            $type = 'checkbox';
            break;

          case StringData::class:
            $type = 'textfield';
            if ($definition['type'] === 'text') {
              $type = 'textarea';
            }
            break;

          case IntegerData::class:
            $type = 'number';
            break;

          default:
            // @todo Mapping config schema types to form element `#type`s makes little sense; there is no guaranteed connection. Move this logic into Drupal core; this might also make #config_target become more useful!
            $type = $definition['type'];
            break;
        }
        $value = $element->getString();
        $build[$key] = [
          '#type' => $type,
          '#title' => $label,
          '#default_value' => $value,
        ];
      }
    }
    return $build;
  }

}
