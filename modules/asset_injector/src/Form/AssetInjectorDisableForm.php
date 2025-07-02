<?php

namespace Drupal\asset_injector\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a confirmation form for disabling asset injector configurations.
 *
 * This form is used to confirm the disabling of an asset injector.
 * It displays a confirmation message and allows the user to either proceed
 * with the disabling or cancel the operation.
 *
 * @package Drupal\asset_injector\Form
 */
class AssetInjectorDisableForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Disable @type: %label?', [
      '@type' => $this->entity->getEntityType()->getLabel(),
      '%label' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Disable @type: %label?', [
      '@type' => $this->entity->getEntityType()->getLabel(),
      '%label' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $type = $this->entity->getEntityType()->get('id');
    return new Url("entity.$type.collection");
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    $entity = $this->entity;
    $entity->disable()->save();

    $this->logger('asset_injector')->notice('%type asset %id disabled', [
      '%type' => $entity->get('entityTypeId'),
      '%id' => $entity->id,
    ]);

    parent::submitForm($form, $form_state);

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
