<?php

declare(strict_types = 1);

namespace Drupal\migrate_tools\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the edit form for our Migration Group entity.
 *
 * @package Drupal\migrate_tools\Form
 *
 * @ingroup migrate_tools
 */
class MigrationGroupEditForm extends MigrationGroupFormBase {

  /**
   * Returns the actions provided by this form.
   *
   * For the edit form, we only need to change the text of the submit button.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An array of supported actions for the current entity form.
   */
  public function actions(array $form, FormStateInterface $form_state): array {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Update Migration Group');
    return $actions;
  }

}
