<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;

/**
 * Provides a plugin for the 'DateTime' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "date_time",
 *   label = @Translation("DateTime"),
 *   property_type = "DateTime",
 *   sub_properties = {},
 * )
 */
class DateTime extends PropertyTypeBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(array $input_values) {
    $form = parent::formElement($input_values);
    $form['#description'] .= ' ' . $this->t('Use a token like [node:created:html_datetime].');
    return $form;
  }

}
