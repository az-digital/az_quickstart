<?php

namespace Drupal\config_distro_ignore\Form;

use Drupal\config_filter\Plugin\ConfigFilterPluginManager;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a setting UI for Config Distro Ignore.
 *
 * @package Drupal\config_distro_ignore\Form
 */
class SettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

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
    return 'config_distro_ignore_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $settings = $this->config('config_distro_ignore.settings');

    $form['all_collections'] = [
      '#type' => 'textarea',
      '#rows' => 25,
      '#title' => $this->t('Configuration for all collections'),
      '#default_value' => implode(PHP_EOL, $settings->get('all_collections')),
      '#size' => 20,
    ];
    $form['default_collection'] = [
      '#type' => 'textarea',
      '#rows' => 25,
      '#title' => $this->t('Configuration for the default collection'),
      '#default_value' => implode(PHP_EOL, $settings->get('default_collection')),
      '#size' => 20,
    ];

    foreach ($this->distroStorage->getAllCollectionNames() as $collection) {
      $key = 'custom_collections.' . $collection;
      $form[strtr($key, ['.' => '_'])] = [
        '#type' => 'textarea',
        '#rows' => 25,
        '#title' => $this->t('Configuration for the @collection collection', ['@collection' => $collection]),
        '#default_value' => implode(PHP_EOL, $settings->get($key) ?? []),
        '#size' => 20,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $this->config('config_distro_ignore.settings');

    self::setArrayValueFromText($settings, $form_state, 'all_collections');
    self::setArrayValueFromText($settings, $form_state, 'default_collection');
    foreach ($this->distroStorage->getAllCollectionNames() as $collection) {
      $key = 'custom_collections.' . $collection;
      self::setArrayValueFromText($settings, $form_state, $key);
    }

    $settings->save();
    parent::submitForm($form, $form_state);

    // Clear the config_filter plugin cache.
    $this->configFilterManager->clearCachedDefinitions();
  }

  /**
   * Set the settings from the form state and the key.
   *
   * @param \Drupal\Core\Config\Config $settings
   *   The configuration to set the elements on.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state to get the data from.
   * @param string $key
   *   The key to extract.
   */
  protected static function setArrayValueFromText(Config &$settings, FormStateInterface $form_state, $key) {
    $values = $form_state->getValue(strtr($key, ['.' => '_']));
    $values = preg_split("[\n|\r]", $values);
    $values = array_filter($values);
    $values = array_unique($values);
    sort($values);

    $settings->set($key, $values);
  }

}
