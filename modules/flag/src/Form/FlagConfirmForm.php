<?php

namespace Drupal\flag\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\flag\Plugin\ActionLink\FormEntryInterface;

/**
 * Provides the confirm form page for flagging an entity.
 *
 * @see \Drupal\flag\Plugin\ActionLink\ConfirmForm
 */
class FlagConfirmForm extends FlagConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flag_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $link_plugin = $this->flag->getLinkTypePlugin();
    return $link_plugin instanceof FormEntryInterface ? $link_plugin->getFlagQuestion() : $this->t('Flag this content');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->flag->getLongText('flag');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    $link_plugin = $this->flag->getLinkTypePlugin();
    return $link_plugin instanceof FormEntryInterface ? $link_plugin->getCreateButtonText() : $this->t('Create flagging');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->flagService->flag($this->flag, $this->entity);
  }

}
