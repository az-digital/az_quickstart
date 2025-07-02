<?php

namespace Drupal\viewsreference\Plugin\Field;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;

/**
 * Defines a list item class for views reference fields.
 */
class ViewsReferenceFieldItemList extends EntityReferenceFieldItemList {

  /**
   * {@inheritdoc}
   */
  public function equals(FieldItemListInterface $list_to_compare) {
    $count1 = count($this);
    $count2 = count($list_to_compare);
    if (0 === $count1 && 0 === $count2) {
      // Both are empty we can safely assume that it did not change.
      return TRUE;
    }
    if ($count1 !== $count2) {
      // One of them is empty but not the other one so the value changed.
      return FALSE;
    }
    $value1 = $this->getValue();
    $value2 = $list_to_compare->getValue();
    if ($value1 === $value2) {
      return TRUE;
    }
    // If the values are not equal ensure a consistent order of field item
    // properties and remove properties which will not be saved.
    $property_definitions = $this->getFieldDefinition()->getFieldStorageDefinition()->getPropertyDefinitions();
    $non_computed_properties = array_filter($property_definitions, function (DataDefinitionInterface $property) {
      return !$property->isComputed();
    });
    $callback = function (&$value) use ($non_computed_properties) {
      if (is_array($value)) {
        $value = array_intersect_key($value, $non_computed_properties);

        // Also filter out properties with a NULL value as they might exist in
        // one field item and not in the other, depending on how the values are
        // set. Do not filter out empty strings or other false-y values as e.g.
        // a NULL or FALSE in a boolean field is not the same.
        $value = array_filter($value, function ($property) {
          return NULL !== $property;
        });

        // Unserialize data property so it can be compared without concern
        // for order.
        if (!empty($value['data']) && is_string($value['data'])) {
          $value['data'] = unserialize($value['data'], ['allowed_classes' => FALSE]);
        }

        $this->recursiveKsort($value);
      }
    };
    array_walk($value1, $callback);
    array_walk($value2, $callback);

    return $value1 == $value2;
  }

  /**
   * Recursively sort array by key.
   *
   * @param array $array
   *   Array to sort recursively.
   *
   * @return bool
   *   Always TRUE.
   */
  protected function recursiveKsort(array &$array) {
    foreach ($array as &$value) {
      if (is_array($value)) {
        $this->recursiveKsort($value);
      }
    }
    return ksort($array);
  }

}
