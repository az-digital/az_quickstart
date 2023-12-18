<?php

namespace Drupal\config_views\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to display entity label optionally linked to entity page.
 *
 * @ViewsField("config_entity_operations")
 */
class ConfigEntityOperations extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\Core\Entity\EntityTypeManager $entity_type_manager */
    $entity_type_manager = \Drupal::entityTypeManager();
    // Doesn't work as there is no alias.
    // $value = $this->getValue($values, 'type');.
    $entity_type = $values->type;
    $entity = $values->entity;
    $links = [];
    if ($entity_type_manager->hasHandler($entity_type, 'list_builder')) {
      $links = $entity_type_manager
        ->getListBuilder($entity_type)
        ->getOperations($entity);
    }
    return [
      '#type' => 'operations',
      '#links' => $links,
    ];
  }

}
