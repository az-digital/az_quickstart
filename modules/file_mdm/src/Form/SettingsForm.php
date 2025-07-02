<?php

declare(strict_types=1);

namespace Drupal\file_mdm\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\file_mdm\Plugin\FileMetadataPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configures file_mdm settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * An array containing the available metadata plugins.
   *
   * @var \Drupal\file_mdm\Plugin\FileMetadataPluginInterface[]
   */
  protected array $metadataPlugins = [];

  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $manager = $container->get(FileMetadataPluginManagerInterface::class);
    foreach ($manager->getDefinitions() as $id => $definition) {
      $instance->metadataPlugins[$id] = $manager->createInstance($id);
    }
    uasort($instance->metadataPlugins, function ($a, $b) {
      return Unicode::strcasecmp((string) $a->getPluginDefinition()['title'], (string) $b->getPluginDefinition()['title']);
    });
    return $instance;
  }

  public function getFormId() {
    return 'file_mdm_settings';
  }

  protected function getEditableConfigNames() {
    return ['file_mdm.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('file_mdm.settings');

    // Missing file logging. Only take for options log levels ERROR or below.
    $levelOptions = array_slice(RfcLogLevel::getLevels(), 3, NULL, TRUE);
    krsort($levelOptions);
    $form['missing_file_log_level'] = [
      '#type' => 'select',
      '#title' => $this->t('Missing file logging'),
      '#description' => $this->t('Log level to use if a file does not exist'),
      '#default_value' => $config->get('missing_file_log_level'),
      '#options' => [-1 => $this->t('- None -')] + $levelOptions,
    ];

    // Cache metadata.
    $form['metadata_cache'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#collapsible' => FALSE,
      '#title' => $this->t('Metadata caching'),
      '#tree' => TRUE,
    ];
    $form['metadata_cache']['settings'] = [
      '#type' => 'file_mdm_caching',
      '#default_value' => $config->get('metadata_cache'),
    ];

    // Settings tabs.
    $form['plugins'] = [
      '#type' => 'vertical_tabs',
      '#tree' => FALSE,
    ];

    // Load subforms from each plugin.
    foreach ($this->metadataPlugins as $id => $plugin) {
      $definition = $plugin->getPluginDefinition();
      $form['file_mdm_plugin_settings'][$id] = [
        '#type' => 'details',
        '#title' => $definition['title'],
        '#description' => $definition['help'],
        '#open' => FALSE,
        '#tree' => TRUE,
        '#group' => 'plugins',
      ];
      $form['file_mdm_plugin_settings'][$id] += $plugin->buildConfigurationForm([], $form_state);
    }

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // Call the form validation handler for each of the plugins.
    foreach ($this->metadataPlugins as $plugin) {
      $plugin->validateConfigurationForm($form, $form_state);
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Call the form submit handler for each of the plugins.
    foreach ($this->metadataPlugins as $plugin) {
      $plugin->submitConfigurationForm($form, $form_state);
    }

    $this->config('file_mdm.settings')
      ->set('metadata_cache', $form_state->getValue(
        ['metadata_cache', 'settings']
      ))
      ->set('missing_file_log_level', $form_state->getValue(
        ['missing_file_log_level']
      ));

    // Only save settings if they have changed to prevent unnecessary cache
    // invalidations.
    if ($this->config('file_mdm.settings')->getOriginal() != $this->config('file_mdm.settings')->get()) {
      $this->config('file_mdm.settings')->save();
    }
    parent::submitForm($form, $form_state);
  }

}
