<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;

/**
 * Provides a plugin for the '@type' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "type",
 *   label = @Translation("@type"),
 *   tree_parent = {
 *     "Organization",
 *   },
 *   tree_depth = -1,
 *   property_type = "@type",
 *   sub_properties = {},
 * )
 */
class Type extends PropertyTypeBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(array $input_values) {

    $options = $this->getOptionList($input_values['tree_parent'], $input_values['tree_depth']);
    $value = $input_values['value'];

    $form['#type'] = 'select';
    $form['#title'] = $input_values['title'];
    $form['#description'] = $input_values['description'];
    $form['#default_value'] = !empty($value) ? $value : '';
    $form['#empty_option'] = ' - ' . $this->t('Select') . ' - ';
    $form['#empty_value'] = '';
    $form['#options'] = $options;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function testValue($type = 'Organization') {
    return is_array($type) ? array_shift($type) : $type;
  }

}
