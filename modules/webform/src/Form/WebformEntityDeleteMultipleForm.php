<?php

namespace Drupal\webform\Form;

/**
 * Provides a webform deletion confirmation form.
 */
class WebformEntityDeleteMultipleForm extends WebformDeleteMultipleFormBase {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    // @see \Drupal\webform\WebformEntityDeleteForm::getDescription
    return [
      'title' => [
        '#markup' => $this->t('This action will…'),
      ],
      'list' => [
        '#theme' => 'item_list',
        '#items' => [
          $this->t('Remove configuration'),
          $this->t('Delete all related submissions'),
          $this->formatPlural(count($this->selection), 'Affect any fields or nodes which reference this webform', 'Affect any fields or nodes which reference these webform', [
            '@item' => $this->entityType->getSingularLabel(),
            '@items' => $this->entityType->getPluralLabel(),
          ]),
        ],
      ],
    ];
  }

}
