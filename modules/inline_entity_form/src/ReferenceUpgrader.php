<?php

namespace Drupal\inline_entity_form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\TypedData\FieldItemDataDefinitionInterface;

/**
 * Class ReferenceUpgrader.
 *
 * When saving nested IEFs inside-out, after saving an inner entity, we must
 * "upgrade" its reference item with e.g. revision ID information.
 *
 * @see \Drupal\inline_entity_form\WidgetSubmit
 */
final class ReferenceUpgrader {

  /**
   * Registered entities, keyed by entity type, then ID.
   *
   * @var array
   */
  private $entities = [];

  /**
   * Registers entities.
   */
  public function registerEntity(EntityInterface $entity) {
    $entityId = $entity->id() ?? $this->throwNeedsId();
    $this->entities[$entity->getEntityTypeId()][$entityId] = $entity;
  }

  /**
   * Throws exceptions on trying to register entities without id.
   */
  private function throwNeedsId() {
    throw new \RuntimeException("Can only register entity with ID.");
  }

  /**
   * Upgrades entities references.
   */
  public function upgradeEntityReferences(FieldableEntityInterface $entity) {
    foreach ($entity as $fieldItemList) {
      if (
        $fieldItemList instanceof EntityReferenceFieldItemListInterface
        && ($targetEntityType = $fieldItemList->getFieldDefinition()->getSetting('target_type'))
        && ($itemDefinition = $fieldItemList->getItemDefinition())
        && $itemDefinition instanceof FieldItemDataDefinitionInterface
        && $itemDefinition->getPropertyDefinition('target_id')
      ) {
        foreach ($fieldItemList as $fieldItem) {
          assert($fieldItem instanceof FieldItemInterface);
          if ($targetId = $fieldItem->target_id) {
            if ($targetEntity = $this->entities[$targetEntityType][$targetId] ?? NULL) {
              $fieldItem->setValue($targetEntity);
            }
          }
        }
      }
    }
  }

}
