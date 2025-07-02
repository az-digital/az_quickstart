<?php

namespace Drupal\blazy\Controller;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a listing of entity optionsets.
 */
abstract class BlazyListBuilderBase extends DraggableListBuilder {

  /**
   * The blazy manager.
   *
   * @var \Drupal\blazy\BlazyManagerInterface
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if (isset($operations['edit'])) {
      $operations['edit']['title'] = $this->t('Configure');
    }

    $operations['duplicate'] = [
      'title'  => $this->t('Duplicate'),
      'weight' => 15,
      'url'    => $entity->toUrl('duplicate-form'),
    ];

    if ($entity->id() == 'default') {
      unset($operations['delete'], $operations['edit']);
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->messenger()->addMessage($this->t('The optionsets order has been updated.'));
  }

}
