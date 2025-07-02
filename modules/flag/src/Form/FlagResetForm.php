<?php

namespace Drupal\flag\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\flag\FlagInterface;
use Drupal\flag\FlagServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the flag reset form.
 */
class FlagResetForm extends ConfirmFormBase {

  /**
   * The Flag Service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * The flag to reset.
   *
   * @var \Drupal\flag\FlagInterface
   */
  protected $flag;

  /**
   * Class constructor.
   *
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   The Flag Service.
   */
  public function __construct(FlagServiceInterface $flag_service) {
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
  public function buildForm(array $form, FormStateInterface $form_state, ?FlagInterface $flag = NULL) {
    $this->flag = $flag;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flag_reset_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to reset the Flag %label?', [
      '%label' => $this->flag->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('All flaggings created with Flag %label will be deleted.', [
      '%label' => $this->flag->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Reset');
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->flagService->unflagAllByFlag($this->flag);
    $this->messenger()->addMessage($this->t('Flag %label was reset.', [
      '%label' => $this->flag->label(),
    ]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
