<?php

namespace Drupal\flag\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\flag\Plugin\ActionLink\FormEntryInterface;

/**
 * Provides the confirm form page for unflagging an entity.
 *
 * @see \Drupal\flag\Plugin\ActionLink\ConfirmForm
 */
class UnflagConfirmForm extends FlagConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unflag_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $link_plugin = $this->flag->getLinkTypePlugin();
    return $link_plugin instanceof FormEntryInterface ? $link_plugin->getUnflagQuestion() : $this->t('Unflag this content');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->flag->getLongText('unflag');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    $link_plugin = $this->flag->getLinkTypePlugin();
    return $link_plugin instanceof FormEntryInterface ? $link_plugin->getDeleteButtonText() : $this->t('Delete flagging');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->flagService->unflag($this->flag, $this->entity);
  }

}
