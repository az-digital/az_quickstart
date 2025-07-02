<?php

namespace Drupal\menu_link_attributes\Form;

use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConfigForm.
 *
 * The configuration form of Menu Link Attributes.
 *
 * @package Drupal\menu_link_attributes\Form
 */
class ConfigForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a ConfigForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The handle of module objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface|null $typedConfigManager
   *   The typed configuration manager or null for older drupal versions.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, ?TypedConfigManagerInterface $typedConfigManager = NULL) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('config.typed') ?? NULL,
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'menu_link_attributes.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'menu_link_attributes_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('menu_link_attributes.config');
    $attributes = $config->get('attributes');
    $conf = ['attributes' => $attributes];
    $config_text = Yaml::encode($conf);

    if (!$this->moduleHandler->moduleExists('yaml_editor')) {
      $message = $this->t('It is recommended to install the <a href="@yaml-editor">YAML Editor</a> module for easier editing.', [
        '@yaml-editor' => 'https://www.drupal.org/project/yaml_editor',
      ]);

      $this->messenger()->addWarning($message);
    }

    $form['config'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Configuration'),
      '#description' => $this->t('Available attributes can be defined in YAML syntax.'),
      '#default_value' => $config_text,
      '#rows' => 15,
      '#attributes' => ['data-yaml-editor' => 'true'],
    ];

    // Use module's YAML config file for example structure.
    $module_path = $this->moduleHandler->getModule('menu_link_attributes')->getPath();
    $yml_text = file_get_contents($module_path . '/config/install/menu_link_attributes.config.yml');

    $form['example'] = [
      '#type' => 'details',
      '#title' => $this->t('Example structure'),
    ];

    $form['example']['description'] = [
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#markup' => $this->t('Each attribute has an optional label and description.'),
    ];

    $form['example']['code'] = [
      '#prefix' => '<pre>',
      '#suffix' => '</pre>',
      '#markup' => $yml_text,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $config_text = $form_state->getValue('config') ?: 'attributes:';

    try {
      $form_state->set('config', Yaml::decode($config_text));
    }
    catch (InvalidDataTypeException $e) {
      $form_state->setErrorByName('config', $e->getMessage());
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $form_state->get('config');
    $this->config('menu_link_attributes.config')
      ->setData($config)
      ->save();
    parent::submitForm($form, $form_state);
  }

}
