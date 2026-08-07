<?php

declare(strict_types=1);

namespace Drupal\az_person_profiles_import\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Quickstart Person Profiles Import settings for this site.
 */
final class AZPersonProfilesImportSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'az_person_profiles_import_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['az_person_profiles_import.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('az_person_profiles_import.settings');

    // Check if az_secrets integration is active by checking if keys exist and
    // have values.
    $using_secrets = $this->hasSecrets(['az_profiles_api_endpoint', 'az_profiles_api_key']);

    if ($using_secrets) {
      $form['secrets_status'] = [
        '#type' => 'item',
        '#markup' => new FormattableMarkup('<div class="messages messages--status">@message1<br>@message2</div>', [
          '@message1' => $this->t('✓ API credentials are managed by Quickstart Secrets Management.'),
          '@message2' => $this->t('Credentials are loaded from environment variables or Pantheon Secrets.'),
        ]),
      ];
    }

    $form['endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Profiles API Endpoint'),
      '#description' => $this->t('Enter a fully qualified URL for the endpoint of the profiles API service.'),
      '#default_value' => $config->get('endpoint'),
      '#required' => !$using_secrets,
      '#disabled' => $using_secrets,
    ];

    if ($using_secrets) {
      $form['endpoint']['#description'] = $this->t('This value is managed by Quickstart Secrets Management and cannot be edited here.');
    }

    $form['apikey'] = [
      '#type' => 'password',
      '#title' => $this->t('API Token'),
      '#description' => $this->t('Enter an API Token for the profiles API service.'),
      '#maxlength' => 128,
      '#size' => 64,
      '#default_value' => $config->get('apikey'),
      '#disabled' => $using_secrets,
    ];

    if ($using_secrets) {
      $form['apikey']['#description'] = $this->t('This value is managed by Quickstart Secrets Management and cannot be edited here.');
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Only save to config if not using secrets.
    if (!$this->hasSecrets(['az_profiles_api_endpoint', 'az_profiles_api_key'])) {
      $this->config('az_person_profiles_import.settings')
        ->set('endpoint', $form_state->getValue('endpoint'))
        ->set('apikey', $form_state->getValue('apikey'))
        ->save();
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Check if secrets are configured with values.
   *
   * @param array $key_ids
   *   Array of key entity IDs to check.
   *
   * @return bool
   *   TRUE if all keys exist and have values, FALSE otherwise.
   */
  protected function hasSecrets(array $key_ids): bool {
    try {
      $key_storage = $this->entityTypeManager->getStorage('key');

      foreach ($key_ids as $key_id) {
        $key = $key_storage->load($key_id);

        if (!$key || !method_exists($key, 'getKeyValue')) {
          return FALSE;
        }

        $value = $key->getKeyValue();
        if (empty($value)) {
          return FALSE;
        }
      }

      return TRUE;
    }
    catch (\Exception $e) {
      // Key storage not available or error loading keys.
      return FALSE;
    }
  }

}
