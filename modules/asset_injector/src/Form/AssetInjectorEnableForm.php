<?php

namespace Drupal\asset_injector\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a confirmation form for enabling asset injector configurations.
 *
 * This form is used to confirm the enabling of an asset injector configuration.
 * It displays a confirmation message and allows the user to either proceed
 * with the enabling or cancel the operation.
 *
 * @package Drupal\asset_injector\Form
 */
class AssetInjectorEnableForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Enable @type: %label?', [
      '@type' => $this->entity->getEntityType()->getLabel(),
      '%label' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Enable @type: %label?', [
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
    $entity->enable()->save();

    $this->logger('asset_injector')->notice('%type asset %id enabled', [
      '%type' => $entity->get('entityTypeId'),
      '%id' => $entity->id(),
    ]);

    parent::submitForm($form, $form_state);

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
