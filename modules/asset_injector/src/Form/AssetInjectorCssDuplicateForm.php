<?php

namespace Drupal\asset_injector\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Duplication form for css asset entities.
 *
 * @package Drupal\asset_injector\Form
 */
class AssetInjectorCssDuplicateForm extends AssetInjectorCssForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\asset_injector\Entity\AssetInjectorCss $entity */
    $entity = $this->entity->createDuplicate();
    $entity->label = $this->t('Duplicate of @label', ['@label' => $this->entity->label()]);
    $this->entity = $entity;
    return parent::form($form, $form_state);
  }

}
