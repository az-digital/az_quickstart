<?php

declare(strict_types=1);

namespace Drupal\google_tag\Plugin\GoogleTag\Event;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;

/**
 * Sign up event plugin.
 *
 * @GoogleTagEvent(
 *   id = "sign_up",
 *   event_name = "sign_up",
 *   label = @Translation("User registration"),
 *   description = @Translation("This event indicates that a user has signed up for an account."),
 *   dependency = "user"
 * )
 */
final class SignUpEvent extends ConfigurableEventBase {

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration(): array {
    return [
      'method' => 'CMS',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    if ($this->configuration['method'] !== UserInterface::REGISTER_ADMINISTRATORS_ONLY) {
      $form = parent::buildConfigurationForm($form, $form_state);
      $form['method'] = [
        '#type' => 'textfield',
        '#title' => 'Signup Method',
        '#default_value' => $this->configuration['method'],
        '#description' => $this->t('Sign up method should not be :admin for GA to work.', [':admin' => UserInterface::REGISTER_ADMINISTRATORS_ONLY]),
        '#maxlength' => '254',
      ];
      return $form;
    }

    $form['markup'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Sign up is :admin, nothing to be configured here.', [':admin' => UserInterface::REGISTER_ADMINISTRATORS_ONLY]),
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  protected function getTokenElements(): array {
    return ['method'];
  }

  /**
   * {@inheritDoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['method'] = $form_state->getValue('method');
  }

}
