<?php

namespace Drupal\paragraphs\Hook;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Entity hooks for the paragraphs module.
 */
class EntityHooks {

  /**
   * Implements hook_entity_duplicate()
   */
  #[Hook('entity_duplicate')]
  public function duplicate(EntityInterface $duplicate, EntityInterface $entity): void {
    if (!$duplicate instanceof ContentEntityInterface) {
      return;
    }
    foreach ($duplicate->getFields() as $field) {
      if ($field->getFieldDefinition()->getType() === 'entity_reference_revisions' && $field->getItemDefinition()->getSetting('target_type') == 'paragraph') {
        foreach ($field as $field_item) {
          if ($field_item->entity instanceof ParagraphInterface) {
            $paragraph = clone $field_item->entity;
            $original_langcodes = array_keys($paragraph->getTranslationLanguages());
            $langcodes = array_keys($duplicate->getTranslationLanguages());
            if ($removed_langcodes = array_diff($original_langcodes, $langcodes)) {
              foreach ($removed_langcodes as $removed_langcode) {
                if ($paragraph->hasTranslation($removed_langcode) && $paragraph->getUntranslated()->language()->getId() != $removed_langcode) {
                  $paragraph->removeTranslation($removed_langcode);
                }
              }
            }
            $field_item->entity = $paragraph->createDuplicate();
          }
        }
      }

    }
  }

}
