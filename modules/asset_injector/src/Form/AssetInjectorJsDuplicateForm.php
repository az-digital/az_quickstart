<?php

namespace Drupal\asset_injector\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for duplicating JavaScript asset injector configurations.
 *
 * This form is used to create a duplicate of an existing JavaScript asset
 * injector configuration. The duplicated configuration will have the same
 * settings as the original but with a new label.
 *
 * @package Drupal\asset_injector\Form
 */
class AssetInjectorJsDuplicateForm extends AssetInjectorJsForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\asset_injector\Entity\AssetInjectorJs $entity */
    $entity = $this->entity->createDuplicate();
    $entity->label = $this->t('Duplicate of @label', ['@label' => $this->entity->label()]);
    $this->entity = $entity;
    return parent::form($form, $form_state);
  }

}
