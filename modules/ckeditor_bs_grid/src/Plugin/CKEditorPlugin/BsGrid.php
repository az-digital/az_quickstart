<?php

namespace Drupal\ckeditor_bs_grid\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\ckeditor\CKEditorPluginCssInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\editor\Entity\Editor;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the "bs_grid" plugin.
 *
 * @CKEditorPlugin(
 *   id = "bs_grid",
 *   label = @Translation("Bootstrap Grid")
 * )
 */
class BsGrid extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface, CKEditorPluginCssInterface, ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected ModuleExtensionList $moduleExtensionList;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, ModuleExtensionList $moduleExtensionList) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->moduleExtensionList = $moduleExtensionList;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('extension.list.module')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    $path = $this->moduleExtensionList->getPath('ckeditor_bs_grid') . '/js/plugins/bs_grid';
    return [
      'bs_grid' => [
        'label' => 'Bootstrap Grid',
        'image' => $path . '/icons/bs_grid.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return $this->moduleExtensionList->getPath('ckeditor_bs_grid') . '/js/plugins/bs_grid/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $settings = $editor->getSettings();
    $config = $settings['plugins']['bs_grid'] ?? [];
    $form['use_cdn'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use BS CDN'),
      '#description' => $this->t('If your theme utilizing CKEditor does not include bootstrap grid classes, or pass them via "ckeditor_stylesheets" then you can include it here. This will ONLY include it for ckeditor.'),
      '#default_value' => $config['use_cdn'] ?? TRUE,
    ];

    $form['cdn_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CDN URL'),
      '#description' => $this->t('The URL to your Bootstrap CDN, default is for grid-only.'),
      '#default_value' => $config['cdn_url'] ?? 'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css',
    ];

    $available_columns = array_combine($r = range(1, 12), $r);
    $form['available_columns'] = [
      '#title' => $this->t('Allowed Columns'),
      '#type' => 'checkboxes',
      '#options' => $available_columns,
      '#default_value' => $config['available_columns'] ?? $available_columns,
      '#prefix' => '<div class="container-inline">',
      '#suffix' => '</div>',
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
      '#default_value' => $config['available_breakpoints'] ?? array_combine($k = array_keys($breakpoint_options), $k),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [
      'core/jquery',
      'core/drupal',
      'core/drupal.ajax',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCssFiles(Editor $editor) {
    $settings = $editor->getSettings();
    $config = $settings['plugins']['bs_grid'] ?? [];
    return !empty($config['use_cdn']) && !empty($config['cdn_url']) ? [$config['cdn_url']] : [];
  }

}
