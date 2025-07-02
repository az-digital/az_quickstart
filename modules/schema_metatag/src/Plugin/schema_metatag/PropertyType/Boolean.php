<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;

/**
 * Provides a plugin for the 'Boolean' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "boolean",
 *   label = @Translation("Boolean"),
 *   property_type = "Boolean",
 *   sub_properties = {},
 * )
 */
class Boolean extends PropertyTypeBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(array $input_values) {
    $value = $input_values['value'];

    $form['#type'] = 'select';
    $form['#title'] = $input_values['title'];
    $form['#description'] = $input_values['description'];
    $form['#default_value'] = !empty($value) ? $value : '';
    $form['#empty_option'] = $this->t('- None -');
    $form['#empty_value'] = '';
    $form['#options'] = [
      'False' => $this->t('False'),
      'True' => $this->t('True'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function testValue($type = '') {
    return 'True';
  }

}
