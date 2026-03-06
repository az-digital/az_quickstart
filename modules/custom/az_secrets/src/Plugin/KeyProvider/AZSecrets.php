<?php

namespace Drupal\az_secrets\Plugin\KeyProvider;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\key\KeyInterface;
use Drupal\key\Plugin\KeyProviderBase;
use Drupal\key\Plugin\KeyPluginFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A key provider to provide fallbacks based on Quickstart best practices.
 *
 * @KeyProvider(
 *   id = "az_secrets",
 *   label = @Translation("Quickstart Secrets Management"),
 *   description = @Translation("A key provider to provide fallbacks based on Quickstart best practices."),
 *   key_value = {
 *     "accepted" = FALSE,
 *     "required" = FALSE
 *   }
 * )
 */
class AZSecrets extends KeyProviderBase implements KeyPluginFormInterface {

  /**
   * A pantheon secrets plugin, wrapped for Composition pattern use.
   *
   * Not always available.
   *
   * @var \Drupal\key\Plugin\KeyProviderInterface
   */
  protected $pantheonSecrets;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'secret_name' => '',
      'base64_encoded' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    // Attempt to get a pantheon secrets plugin if one is available.
    try {
      $instance->pantheonSecrets = $container->get('plugin.manager.key.key_provider')->createInstance('pantheon', $configuration);
    }
    catch (PluginNotFoundException $e) {
      // This is not an error. Pantheon secrets may not be installed/available.
    }
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['secret_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret or environment variable name'),
      '#description' => $this->t('This secret will be drawn from the Pantheon secrets client or environment variables.'),
      '#required' => TRUE,
      '#default_value' => $this->getConfiguration()['secret_name'],
    ];

    // Add an option to indicate that the value is stored Base64-encoded.
    $form['base64_encoded'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Base64-encoded'),
      '#description' => $this->t('Check this if the key in the variable is Base64-encoded. <em>Note: Naturally Base64-encoded values, such as RSA keys, do not need to be marked as Base64-encoded unless they have been additionally encoded for another reason.</em>'),
      '#default_value' => $this->getConfiguration()['base64_encoded'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // A secret not existing may be a distro-managed secret that has no value.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration($form_state->getValues());
    if (isset($this->pantheonSecrets) && ($this->pantheonSecrets instanceof KeyProviderBase)) {
      // Set our optional wrapped plugin with the same configuration.
      $this->pantheonSecrets->setConfiguration($this->getConfiguration());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getKeyValue(KeyInterface $key) {
    // Use our wrapped pantheon secrets plugin if we've got one.
    if (defined('PANTHEON_ENVIRONMENT') && isset($this->pantheonSecrets)) {
      return $this->pantheonSecrets->getKeyValue($key);
    }

    // Fallback to environment variables if no secrets available.
    $secret_name = $this->configuration['secret_name'];
    $key_value = getenv($secret_name);

    if (!$key_value) {
      return NULL;
    }

    if (isset($this->configuration['base64_encoded']) && $this->configuration['base64_encoded'] === TRUE) {
      // phpcs:ignore
      $key_value = base64_decode($key_value);
    }

    return $key_value;
  }

}
