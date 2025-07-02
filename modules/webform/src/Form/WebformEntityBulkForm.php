<?php

namespace Drupal\webform\Form;

/**
 * Provides the webform bulk form.
 */
class WebformEntityBulkForm extends WebformBulkFormBase {

  /**
   * {@inheritdoc}
   */
  protected $entityTypeId = 'webform';

  /**
   * {@inheritdoc}
   */
  protected function getActions() {
    $actions = parent::getActions();
    $is_archived = ($this->getRequest()->query->get('state') === 'archived');
    if ($is_archived) {
      unset(
        $actions['webform_archive_action'],
        $actions['webform_open_action'],
        $actions['webform_close_action']
      );
    }
    else {
      unset($actions['webform_unarchive_action']);
    }
    return $actions;
  }

}
