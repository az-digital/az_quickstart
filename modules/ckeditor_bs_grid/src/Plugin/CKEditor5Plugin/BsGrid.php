<?php

namespace Drupal\ckeditor_bs_grid\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\editor\EditorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Bootstrap Grid Config for CKE5.
 */
class BsGrid extends CKEditor5PluginDefault implements ContainerFactoryPluginInterface, CKEditor5PluginConfigurableInterface {

  use CKEditor5PluginConfigurableTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'));
  }

  /**
   * Grid Config constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(array $configuration, string $plugin_id, CKEditor5PluginDefinition $plugin_definition, ConfigFactoryInterface $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration() {
    $available_columns = array_combine($r = range(1, 12), array_map('strval', $r));
    return [
      'use_cdn' => TRUE,
      'cdn_url' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css',
      'available_columns' => $available_columns,
      'available_breakpoints' => [
        'xs' => 'xs',
        'sm' => 'sm',
        'md' => 'md',
        'lg' => 'lg',
        'xl' => 'xl',
        'xxl' => 'xxl',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    $dynamic_plugin_config = $static_plugin_config;

    $dynamic_plugin_config['bootstrapGrid']['dialogURL'] = Url::fromRoute('ckeditor_bs_grid.dialog')
      ->setRouteParameter('editor', $editor->getFilterFormat()->id())
      ->toString(TRUE)
      ->getGeneratedUrl();

    $dynamic_plugin_config['bootstrapGrid'] = array_merge(
      $dynamic_plugin_config['bootstrapGrid'],
      $this->getConfiguration()
    );

    return $dynamic_plugin_config;
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['use_cdn'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use BS CDN'),
      '#description' => $this->t('If your theme utilizing CKEditor does not include bootstrap grid classes, or pass them via "ckeditor_stylesheets" then you can include it here. This will ONLY include it for ckeditor.'),
      '#default_value' => $this->configuration['use_cdn'],
    ];

    $form['cdn_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CDN URL'),
      '#description' => $this->t('The URL to your Bootstrap CDN, default is for grid-only.'),
      '#default_value' => $this->configuration['cdn_url'],
    ];

    $available_columns = array_combine($r = range(1, 12), $r);
    $form['available_columns'] = [
      '#title' => $this->t('Allowed Columns'),
      '#type' => 'checkboxes',
      '#options' => $available_columns,
      '#default_value' => $this->configuration['available_columns'],
      '#required' => TRUE,
    ];

    $bs_breakpoints = $this->configFactory->get('ckeditor_bs_grid.settings')->get('breakpoints');
    $breakpoint_options = [];
    foreach ($bs_breakpoints as $class => $breakpoint) {
      $breakpoint_options[$class] = $breakpoint['label'];
    }
    $form['available_breakpoints'] = [
      '#title' => $this->t('Allowed Breakpoints'),
      '#type' => 'checkboxes',
      '#options' => $breakpoint_options,
      '#default_value' => $this->configuration['available_breakpoints'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('use_cdn', (bool) $form_state->getValue('use_cdn'));
    $form_state->setValue('cdn_url', (string) $form_state->getValue('cdn_url'));
    $form_state->setValue('available_columns', array_values(array_filter($form_state->getValue('available_columns'))));
    $form_state->setValue('available_breakpoints', array_values(array_filter($form_state->getValue('available_breakpoints'))));
  }

  /**
   * {@inheritDoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['use_cdn'] = $form_state->getValue('use_cdn');
    $this->configuration['cdn_url'] = $form_state->getValue('cdn_url');
    $this->configuration['available_columns'] = $form_state->getValue('available_columns');
    $this->configuration['available_breakpoints'] = $form_state->getValue('available_breakpoints');
  }

}
