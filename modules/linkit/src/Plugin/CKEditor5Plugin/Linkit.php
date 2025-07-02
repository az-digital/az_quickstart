<?php

declare(strict_types=1);

namespace Drupal\linkit\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginElementsSubsetInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\editor\EditorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * CKEditor 5 Linkit plugin configuration.
 */
class Linkit extends CKEditor5PluginDefault implements CKEditor5PluginElementsSubsetInterface, ContainerFactoryPluginInterface {

  use CKEditor5PluginConfigurableTrait;

  /**
   * The Linkit profile storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $linkitProfileStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $linkit_profile_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->linkitProfileStorage = $linkit_profile_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('linkit_profile')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsSubset(): array {
    // @see \Drupal\linkit\Plugin\Filter\LinkitFilter
    return ['<a data-entity-type data-entity-uuid data-entity-substitution>'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $all_profiles = $this->linkitProfileStorage->loadMultiple();

    $options = [];
    foreach ($all_profiles as $profile) {
      $options[$profile->id()] = $profile->label();
    }

    $form['linkit_profile'] = [
      '#wrapper_attributes' => ['class' => ['container-inline']],
      '#type' => 'select',
      '#title' => $this->t('Linkit profile'),
      '#options' => $options,
      '#default_value' => $this->configuration['linkit_profile'] ?? '',
      '#empty_option' => $this->t('- Linkit disabled -'),
    ];

    return $form;
  }

  /**
   * Config validation callback: require linkit_profile if linkit_enabled=TRUE.
   *
   * @param array $values
   *   The configuration subtree for ckeditor5.plugin.linkit_extension.
   * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
   *   The validation execution context.
   *
   * @see linkit.schema.yml
   */
  public static function requireProfileIfEnabled(array $values, ExecutionContextInterface $context): void {
    if ($values['linkit_enabled'] === TRUE && empty($values['linkit_profile'])) {
      $context->buildViolation('Linkit is enabled, please select the Linkit profile you wish to use.')
        ->atPath('linkit_profile')
        ->addViolation();
    }
    elseif ($values['linkit_enabled'] === FALSE && !empty($values['linkit_profile'])) {
      $context->buildViolation('Linkit is disabled; it does not make sense to associate a Linkit profile.')
        ->atPath('linkit_profile')
        ->addViolation();
    }
  }

  /**
   * Computes all valid choices for the "linkit_profile" setting.
   *
   * @see linkit.schema.yml
   *
   * @return string[]
   *   All valid choices.
   */
  public static function validChoices(): array {
    $linkit_profile_storage = \Drupal::service('entity_type.manager')->getStorage('linkit_profile');
    assert($linkit_profile_storage instanceof EntityStorageInterface);
    return array_keys($linkit_profile_storage->loadMultiple());
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Match the config schema structure at ckeditor5.plugin.linkit_extension.
    if (empty($form_state->getValue('linkit_profile'))) {
      $form_state->unsetValue('linkit_profile');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['linkit_enabled'] = $form_state->hasValue('linkit_profile');
    // `linkit_profile` only is relevant when Linkit is enabled.
    if ($this->configuration['linkit_enabled']) {
      $this->configuration['linkit_profile'] = $form_state->getValue('linkit_profile');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'linkit_enabled' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    assert($this->configuration['linkit_enabled'] === TRUE);
    return [
      'linkit' => [
        'profile' => $this->configuration['linkit_profile'],
        'autocompleteUrl' => Url::fromRoute('linkit.autocomplete', ['linkit_profile_id' => $this->configuration['linkit_profile']])
          ->toString(TRUE)
          ->getGeneratedUrl()
      ],
    ];
  }

}
