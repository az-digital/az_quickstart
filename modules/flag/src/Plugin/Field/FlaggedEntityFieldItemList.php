<?php

namespace Drupal\flag\Plugin\Field;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * A computed entity reference field item list.
 */
class FlaggedEntityFieldItemList extends EntityReferenceFieldItemList {

  use ComputedItemListTrait;

  /**
   * Returns the label as the field item.
   */
  protected function computeValue() {
    // @phpstan-ignore-next-line
    $this->list[0] = $this->createItem(0, $this->getEntity()->value);
  }

}
