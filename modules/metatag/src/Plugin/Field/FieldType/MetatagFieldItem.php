<?php

namespace Drupal\metatag\Plugin\Field\FieldType;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'metatag' field type.
 *
 * @FieldType(
 *   id = "metatag",
 *   label = @Translation("Meta tags"),
 *   description = @Translation("This field stores code meta tags."),
 *   list_class = "\Drupal\metatag\Plugin\Field\FieldType\MetatagFieldItemList",
 *   default_widget = "metatag_firehose",
 *   default_formatter = "metatag_empty_formatter",
 * )
 */
class MetatagFieldItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'text',
          'size' => 'big',
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('metatag')
      ->setLabel(t('Metatag'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '' || $value === Json::encode([]);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();

    // Merge field defaults on top of global ones.
    $default_tags = metatag_get_default_tags($this->getEntity());

    // Get the value about to be saved.
    // @todo Does this need to be rewritten to use $this->getValue()?
    $current_value = $this->getValue()['value'] ?? '';

    // Only unserialize if still serialized string.
    if (is_string($current_value)) {
      $current_tags = metatag_data_decode($current_value);
    }
    else {
      $current_tags = $current_value;
    }

    // Only include values that differ from the default.
    // @todo When site defaults are added, account for those.
    $tags_to_save = [];
    foreach ($current_tags as $tag_id => $tag_value) {
      if (!isset($default_tags[$tag_id]) || ($tag_value != $default_tags[$tag_id])) {
        $tags_to_save[$tag_id] = $tag_value;
      }
    }

    // Sort the values prior to saving. so that they are easier to manage.
    ksort($tags_to_save);

    // Update the value to only save overridden tags.
    $this->setValue(['value' => Json::encode($tags_to_save)]);
  }

}
