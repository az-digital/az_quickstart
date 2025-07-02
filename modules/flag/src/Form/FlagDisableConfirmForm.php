<?php

namespace Drupal\flag\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\flag\FlagInterface;

/**
 * Provides the flag enable/disable confirmation form.
 */
class FlagDisableConfirmForm extends ConfirmFormBase {

  /**
   * The flag to be enabled or disabled.
   *
   * @var \Drupal\flag\FlagInterface
   */
  protected $flag;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?FlagInterface $flag = NULL) {
    $this->flag = $flag;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flag_disable_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    if ($this->flag->status()) {
      return $this->t('Disable flag @name?', ['@name' => $this->flag->label()]);
    }

    return $this->t('Enable flag @name?', ['@name' => $this->flag->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->flag->toUrl('collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    if ($this->flag->status()) {
      return $this->t('Users will no longer be able to use the flag, but no data will be lost.');
    }

    return $this->t('The flag will appear once more on configured nodes.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    if ($this->flag->status()) {
      return $this->t('Disable');
    }

    return $this->t('Enable');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Toggle the flag state.
    if ($this->flag->status()) {
      $this->flag->disable();
    }
    else {
      $this->flag->enable();
    }

    // Save The flag entity.
    $this->flag->save();

    // Redirect to the flag admin page.
    $form_state->setRedirect('entity.flag.collection');
  }

}
