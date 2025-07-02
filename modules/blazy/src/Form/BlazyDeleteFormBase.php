<?php

namespace Drupal\blazy\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the form to delete an optionset.
 */
abstract class BlazyDeleteFormBase extends EntityConfirmFormBase {

  /**
   * Defines the nice anme.
   *
   * @var string
   */
  protected static $niceName = 'Slick';

  /**
   * Defines machine name.
   *
   * @var string
   */
  protected static $machineName = 'slick';

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the %name optionset %label?', [
      '%name' => static::$niceName,
      '%label' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    $this->messenger()->addMessage($this->t('The %name optionset %label has been deleted.', [
      '%name' => static::$niceName,
      '%label' => $this->entity->label(),
    ]));
    $this->logger(static::$machineName)->notice('Deleted optionset %oid (%label)', [
      '%oid' => $this->entity->id(),
      '%label' => $this->entity->label(),
    ]);

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
