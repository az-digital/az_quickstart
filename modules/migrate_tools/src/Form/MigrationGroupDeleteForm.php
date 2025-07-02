<?php

declare(strict_types = 1);

namespace Drupal\migrate_tools\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Provides the delete form for our Migration Group entity.
 *
 * @package Drupal\migrate_tools\Form
 *
 * @ingroup migrate_tools
 */
class MigrationGroupDeleteForm extends EntityConfirmFormBase {

  /**
   * Gathers a confirmation question.
   *
   * @return string
   *   Translated string.
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Are you sure you want to delete migration group %label?', [
      '%label' => $this->entity->label(),
    ]);
  }

  /**
   * Gather the confirmation text.
   *
   * @return string
   *   Translated string.
   */
  public function getConfirmText(): TranslatableMarkup {
    return $this->t('Delete Migration Group');
  }

  /**
   * Gets the cancel URL.
   *
   * @return \Drupal\Core\Url
   *   The URL to go to if the user cancels the deletion.
   */
  public function getCancelUrl(): Url {
    return new Url('entity.migration_group.list');
  }

  /**
   * The submit handler for the confirm form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Delete the entity.
    $this->entity->delete();

    // Set a message that the entity was deleted.
    $this->messenger()->addStatus($this->t('Migration group %label was deleted.', [
      '%label' => $this->entity->label(),
    ]));

    // Redirect the user to the list controller when complete.
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
