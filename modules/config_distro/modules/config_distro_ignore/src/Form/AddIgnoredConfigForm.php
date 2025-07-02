<?php

namespace Drupal\config_distro_ignore\Form;

use Drupal\config_distro_ignore\Plugin\ConfigFilter\DistroIgnoreFilter;
use Drupal\config_filter\Plugin\ConfigFilterPluginManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides form for adding ignored configuration.
 *
 * @package Drupal\config_distro_ignore\Form
 */
class AddIgnoredConfigForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * The distro storage to know what collections we have.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $distroStorage;

  /**
   * The config_filter plugin manager.
   *
   * @var \Drupal\config_filter\Plugin\ConfigFilterPluginManager
   */
  protected $configFilterManager;

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory for the parent.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\Core\Config\StorageInterface $distro_storage
   *   The distro storage to know what collections we have.
   * @param \Drupal\config_filter\Plugin\ConfigFilterPluginManager $config_filter_manager
   *   The config_filter plugin manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
    StorageInterface $distro_storage,
    ConfigFilterPluginManager $config_filter_manager,
  ) {
    parent::__construct(
      $config_factory,
      $typedConfigManager
    );
    $this->distroStorage = $distro_storage;
    $this->configFilterManager = $config_filter_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('config_distro.storage.distro'),
      $container->get('plugin.manager.config_filter')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'config_distro_ignore.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_distro_ignore_add_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $config_name = NULL, $collection = NULL) {
    $form = parent::buildForm($form, $form_state);

    $form['info'] = [
      '#markup' => $this->t('Retain the configuration: @config', ['@config' => $config_name]),
    ];

    $form['name'] = [
      '#type' => 'hidden',
      '#value' => $config_name,
    ];
    $form['collection'] = [
      '#type' => 'hidden',
      '#value' => $collection,
    ];

    $form['type'] = [
      '#type' => 'radios',
      '#title' => $this->t('How to retain.'),
      '#default_value' => 'permanent',
      '#options' => [
        'permanent' => $this->t('Permanently'),
        'hash' => $this->t('This specific version'),
      ],
    ];

    $names = $this->distroStorage->getAllCollectionNames();
    $options = [
      'all' => $this->t('All collections'),
      'default' => $this->t('Default collection'),
    ] + array_combine($names, $names);

    $form['apply_collection'] = [
      '#type' => 'select',
      '#title' => $this->t('Collection'),
      '#default_value' => 'all',
      '#options' => $options,
    ];

    if ($collection) {
      $form['apply_collection']['#default_value'] = $collection;
    }

    if (empty($names)) {
      $form['apply_collection']['#access'] = FALSE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $name = $form_state->getValue('name');
    if (!strlen($name)) {
      throw new \RuntimeException('The config name can not be empty');
    }

    $collection = $form_state->getValue('apply_collection');
    if (!strlen($collection)) {
      throw new \RuntimeException('The collection can not be empty');
    }

    if ($collection == 'all' && $form_state->getValue('type') == 'hash') {
      // When ignoring just the hash but for all collections, we save the hash
      // In the collection keys instead of the all key.
      $hash = DistroIgnoreFilter::hashConfig($this->distroStorage->read($name));
      $this->addNameToConfig('default_collection', $name . DistroIgnoreFilter::HASH_SEPARATOR . $hash);
      // Create hashes for all collections.
      foreach ($this->distroStorage->getAllCollectionNames() as $collectionName) {
        $storage = $this->distroStorage->createCollection($collectionName);
        $hash = DistroIgnoreFilter::hashConfig($storage->read($name));
        $this->addNameToConfig('custom_collections.' . $collectionName, $name . DistroIgnoreFilter::HASH_SEPARATOR . $hash);
      }
    }
    else {
      // Select the config key to use.
      switch ($collection) {
        case 'all':
          $key = 'all_collections';
          break;

        case 'default':
          $key = 'default_collection';
          break;

        default:
          $key = 'custom_collections.' . $collection;
          if ($collection != StorageInterface::DEFAULT_COLLECTION) {
            // Set the distro storage to use the collection so that the hash is
            // later calculated correctly.
            $this->distroStorage = $this->distroStorage->createCollection($collection);
          }
          break;
      }

      // Save the name in the list.
      if ($form_state->getValue('type') == 'hash') {
        // Create the hash of the config as read from the distro storage.
        $hash = DistroIgnoreFilter::hashConfig($this->distroStorage->read($name));
        $this->addNameToConfig($key, $name . DistroIgnoreFilter::HASH_SEPARATOR . $hash);
      }
      else {
        $this->addNameToConfig($key, $name);
      }
    }

    // Redirect back to the page we came from.
    $form_state->setRedirect('config_distro.import');

    // Clear the config_filter plugin cache.
    $this->configFilterManager->clearCachedDefinitions();
  }

  /**
   * Add a config name to the list.
   *
   * @param string $key
   *   The config key.
   * @param string $name
   *   The config name to add to the list.
   */
  protected function addNameToConfig($key, $name) {
    $settings = $this->config('config_distro_ignore.settings');
    $data = $settings->get($key);

    $data[] = $name;
    $data = array_filter($data);
    $data = array_unique($data);
    sort($data);

    $settings->set($key, $data);
    $settings->save();
  }

}
