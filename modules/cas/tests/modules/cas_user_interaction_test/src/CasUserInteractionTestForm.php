<?php

namespace Drupal\cas_user_interaction_test;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form that enforces the user to accept the new site's 'Legal Notice'.
 */
class CasUserInteractionTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['accept'] = [
      '#type' => 'checkbox',
      '#title' => "I agree with the 'Legal Notice'",
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'I agree',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\cas\Service\CasUserManager $cas_user_manager */
    $cas_user_manager = \Drupal::service('cas.user_manager');

    /** @var \Drupal\Core\TempStore\PrivateTempStore $tempstore */
    $tempstore = \Drupal::service('tempstore.private')->get('cas_user_interaction_test');

    $cas_user_manager->login($tempstore->get('property_bag'), $tempstore->get('ticket'));
    $form_state->setRedirectUrl(Url::fromUserInput('/'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cas_user_interaction_test_form';
  }

}
