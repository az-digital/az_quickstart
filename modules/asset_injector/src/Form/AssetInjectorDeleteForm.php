<?php

namespace Drupal\asset_injector\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete Asset Injector entities.
 */
class AssetInjectorDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete @type: %name?', [
      '@type' => $this->entity->getEntityType()->getLabel(),
      '%name' => $this->entity->label(),
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
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    $entity = $this->entity;
    $this->logger('asset_injector')->notice('%type asset %id deleted', [
      '%type' => $entity->get('entityTypeId'),
      '%id' => $entity->id(),
    ]);

    $entity->delete();

    $this->messenger()->addMessage($this->t('@type deleted @label.', [
      '@type' => $entity->getEntityType()->getLabel(),
      '@label' => $entity->label(),
    ]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
