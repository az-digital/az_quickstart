<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;

/**
 * Provides a plugin for the 'Text' Schema.org property type.
 *
 * This type converts values to plain text. HTML content is removed from the
 * value during processing.
 *
 * @see \Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase::processItem()
 *
 * @SchemaPropertyType(
 *   id = "text",
 *   label = @Translation("Text"),
 *   property_type = "Text",
 *   sub_properties = {},
 * )
 */
class Text extends PropertyTypeBase {

  /**
   * {@inheritdoc}
   */
  public function testValue($type = '') {
    return '<p>A string <strong>with</strong> <em>some</em> <a href="https://www.drupal.org">HTML</a>!</p>';
  }

  /**
   * {@inheritdoc}
   */
  public function processedTestValue($items) {
    return 'A string with some HTML!';
  }

}
