<?php

namespace Drupal\flag\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\flag\FlagInterface;
use Drupal\flag\FlagService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides methods common to the flag and unflag confirm forms.
 *
 * @see \Drupal\flag\Plugin\ActionLink\ConfirmForm
 */
abstract class FlagConfirmFormBase extends ConfirmFormBase {

  /**
   * The flaggable entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The flag entity.
   *
   * @var \Drupal\flag\FlagInterface
   */
  protected $flag;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagService
   */
  protected $flagService;

  /**
   * Constructs a FlagConfirmFormBase object.
   *
   * @param \Drupal\flag\FlagService $flag_service
   *   The flag service.
   */
  public function __construct(FlagService $flag_service) {
    $this->flagService = $flag_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('flag')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?FlagInterface $flag = NULL, $entity_id = NULL) {
    $this->flag = $flag;
    $this->entity = $this->flagService->getFlaggableById($this->flag, $entity_id);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->toUrl();
  }

  /**
   * Returns the confirm form's flag entity.
   */
  public function getFlag() {
    return $this->flag;
  }

  /**
   * Returns the confirm form's flaggable entity.
   */
  public function getEntity() {
    return $this->entity;
  }

}
