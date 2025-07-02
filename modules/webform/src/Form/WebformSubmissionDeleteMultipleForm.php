<?php

namespace Drupal\webform\Form;

/**
 * Provides a webform submission deletion confirmation form.
 */
class WebformSubmissionDeleteMultipleForm extends WebformDeleteMultipleFormBase {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    // @see \Drupal\webform\Form\WebformSubmissionDeleteForm::getDescription
    return [
      'title' => [
        '#markup' => $this->t('This action will…'),
      ],
      'list' => [
        '#theme' => 'item_list',
        '#items' => [
          $this->t('Remove records from the database'),
          $this->t('Delete any uploaded files'),
          $this->t('Cancel all pending actions'),
        ],
      ],
    ];
  }

}
