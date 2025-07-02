<?php

namespace Drupal\masquerade\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\masquerade\Masquerade;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form builder for the masquerade form.
 */
class MasqueradeForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The masquerade service.
   *
   * @var \Drupal\masquerade\Masquerade
   */
  protected $masquerade;

  /**
   * Constructs a MasqueradeForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $etm
   *   The entity type manager.
   * @param \Drupal\masquerade\Masquerade $masquerade
   *   The masquerade service.
   */
  public function __construct(EntityTypeManagerInterface $etm, Masquerade $masquerade) {
    $this->entityTypeManager = $etm;
    $this->masquerade = $masquerade;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('masquerade')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'masquerade_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['autocomplete'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['container-inline']],
    ];
    $form['autocomplete']['masquerade_as'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#selection_settings' => [
        'include_anonymous' => FALSE,
        'match_operator' => 'STARTS_WITH',
      ],
      '#title' => $this->t('Username'),
      '#title_display' => 'invisible',
      '#required' => TRUE,
      '#placeholder' => $this->t('Masquerade as…'),
      '#size' => '18',
    ];
    $form['autocomplete']['actions'] = ['#type' => 'actions'];
    $form['autocomplete']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Switch'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $user_id = $form_state->getValue('masquerade_as');
    if (empty($user_id)) {
      $form_state->setErrorByName('masquerade_as', $this->t('The user does not exist. Please enter a valid username.'));
      return;
    }
    $target_account = $this->entityTypeManager
      ->getStorage('user')
      ->load($user_id);
    if ($error = masquerade_switch_user_validate($target_account)) {
      $form_state->setErrorByName('masquerade_as', $error);
    }
    else {
      $form_state->setValue('masquerade_target_account', $target_account);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->masquerade->switchTo($form_state->getValue('masquerade_target_account'));
  }

}
