<?php

namespace Drupal\webform;

use Drupal\webform\Form\WebformConfigEntityDeleteFormBase;

/**
 * Provides a delete webform form.
 */
class WebformEntityDeleteForm extends WebformConfigEntityDeleteFormBase {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    // @see \Drupal\webform\Form\WebformEntityDeleteMultipleForm::getDescription
    return [
      'title' => [
        '#markup' => $this->t('This action will…'),
      ],
      'list' => [
        '#theme' => 'item_list',
        '#items' => [
          $this->t('Remove configuration'),
          $this->t('Delete all related submissions'),
          $this->t('Affect any fields or nodes which reference this webform'),
        ],
      ],
    ];
  }

}
