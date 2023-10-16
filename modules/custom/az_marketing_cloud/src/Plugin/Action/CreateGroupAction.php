<?php

namespace Drupal\az_marketing_cloud\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Create a group.
 *
 * @Action(
 *   id = "az_marketing_cloud_create_group_action",
 *   label = @Translation("Create a group"),
 *   type = ""
 * )
 */
class CreateGroupAction extends ViewsBulkOperationsActionBase
{

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL)
  {
    // Do some processing..

    // Don't return anything for a default completion message, otherwise return translatable markup.
    return $this->t('Some result');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE)
  {
    // If certain fields are updated, access should be checked against them as well.
    // @see Drupal\Core\Field\FieldUpdateActionBase::access().
    return $object->access('update', $account, $return_as_object);
  }
}
