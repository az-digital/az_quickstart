<?php

namespace Drupal\views_bulk_operations\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;

/**
 * Delete entity action.
 */
#[Action(
  id: 'views_bulk_operations_delete_entity',
  label: new TranslatableMarkup('Delete selected entities / translations'),
  type: ''
)]
class EntityDeleteAction extends ViewsBulkOperationsActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity instanceof TranslatableInterface && !$entity->isDefaultTranslation()) {
      try {
        $untranslated_entity = $entity->getUntranslated();
        $untranslated_entity->removeTranslation($entity->language()->getId());
        $untranslated_entity->save();
      }
      catch (EntityStorageException $e) {
        // If the untranslated entity got deleted before
        // the translated one, an EntityStorageException will be thrown.
        // We can ignore it as the translated entity will be deleted anyway.
      }
      return $this->t('Delete translations');
    }
    else {
      $entity->delete();
      return $this->t('Delete entities');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('delete', $account, $return_as_object);
  }

}
