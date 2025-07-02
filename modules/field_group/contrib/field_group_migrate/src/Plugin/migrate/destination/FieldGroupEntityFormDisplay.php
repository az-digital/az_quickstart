<?php

namespace Drupal\field_group_migrate\Plugin\migrate\destination;

use Drupal\migrate\Plugin\migrate\destination\PerComponentEntityFormDisplay;
use Drupal\migrate\Row;

/**
 * This class imports one field_group of an entity form display.
 *
 * @MigrateDestination(
 *   id = "field_group_entity_form_display"
 * )
 */
class FieldGroupEntityFormDisplay extends PerComponentEntityFormDisplay {

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $values = [];
    // array_intersect_key() won't work because the order is important because
    // this is also the return value.
    foreach (array_keys($this->getIds()) as $id) {
      $values[$id] = $row->getDestinationProperty($id);
    }
    $entity = $this->getEntity($values['entity_type'], $values['bundle'], $values[static::MODE_NAME]);
    $settings = $row->getDestinationProperty('field_group');
    $settings += [
      'region' => 'content',
      'parent_name' => '',
    ];
    $entity->setThirdPartySetting('field_group', $row->getDestinationProperty('id'), $settings);
    if (isset($settings['format_type']) && ($settings['format_type'] == 'no_style' || $settings['format_type'] == 'hidden')) {
      $entity->unsetThirdPartySetting('field_group', $row->getDestinationProperty('id'));
    }
    $entity->save();
    return array_values($values);
  }

}
