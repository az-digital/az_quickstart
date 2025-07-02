<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;

/**
 * Provides a plugin for the 'Duration' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "duration",
 *   label = @Translation("Duration"),
 *   property_type = "Duration",
 *   sub_properties = {},
 * )
 */
class Duration extends PropertyTypeBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(array $input_values) {
    $form = parent::formElement($input_values);
    $form['#description'] .= ' ' . $this->t('Use a token like [node:created:html_datetime].');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function outputValue($input_value) {
    $is_integer = ctype_digit($input_value) || is_int($input_value);
    if (!empty($input_value) && $is_integer && $input_value > 0) {
      return 'PT' . $input_value . 'S';
    }
    return $input_value;
  }

}
