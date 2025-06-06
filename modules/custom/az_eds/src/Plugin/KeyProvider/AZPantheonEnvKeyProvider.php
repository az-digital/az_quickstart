<?php

namespace Drupal\az_eds\Plugin\KeyProvider;

use Drupal\Core\Form\FormStateInterface;
use Drupal\pantheon_secrets\Plugin\KeyProvider\PantheonSecretKeyProvider;
use Drupal\key\KeyInterface;

/**
 * A key provider that fetches a key from Pantheon secrets, or the environment.
 *
 * @KeyProvider(
 *   id = "az_pantheon_env",
 *   label = @Translation("Quickstart Pantheon Secret or Environment variable"),
 *   description = @Translation("This key provider retrieves from a Pantheon secret or a environment variable fallback."),
 *   storage_method = "pantheon",
 *   key_value = {
 *     "accepted" = FALSE,
 *     "required" = FALSE
 *   }
 * )
 */
class AZPantheonEnvKeyProvider extends PantheonSecretKeyProvider {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    // If not on pantheon, secret_name should become text input.
    if (!defined('PANTHEON_ENVIRONMENT')) {
      $form['secret_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Secret name or environment variable'),
        '#description' => $this->t('Name of the secret or environment variable.'),
        '#required' => TRUE,
        '#default_value' => $this->getConfiguration()['secret_name'],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $key_provider_settings = $form_state->getValues();
    $secret_name = $key_provider_settings['secret_name'];
    // Don't throw an error for a missing secret.
    if (defined('PANTHEON_ENVIRONMENT')) {
      $secret_value = $this->secretsClient->getSecret($secret_name);

      // Does the secret exist.
      if (!$secret_value) {
        $form_state->setErrorByName('secret_name', $this->t('The secret does not exist or it is empty.'));
        return;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getKeyValue(KeyInterface $key) {
    // If we're on pantheon, use secrets exclusively.
    if (defined('PANTHEON_ENVIRONMENT')) {
      return parent::getKeyValue($key);
    }
    // Environment variable fallback.
    // Unlike EnvKeyProvider, we use the pantheon config key of secret_name.
    $env_variable = $this->configuration['secret_name'];
    $key_value = getenv($env_variable);

    if (!$key_value) {
      return NULL;
    }

    if (isset($this->configuration['base64_encoded']) && $this->configuration['base64_encoded'] === TRUE) {
      // Phpstan doesn't like base64_decode.
      // @phpstan-ignore-next-line
      // phpcs:ignore
      $key_value = base64_decode($key_value);
    }
    return $key_value;
  }

}
