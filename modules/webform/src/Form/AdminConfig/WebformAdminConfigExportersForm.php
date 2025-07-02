<?php

namespace Drupal\webform\Form\AdminConfig;

use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure webform admin settings for exporters.
 */
class WebformAdminConfigExportersForm extends WebformAdminConfigBaseForm {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The webform exporter manager.
   *
   * @var \Drupal\webform\Plugin\WebformExporterManagerInterface
   */
  protected $exporterManager;

  /**
   * The webform submission exporter.
   *
   * @var \Drupal\webform\WebformSubmissionExporterInterface
   */
  protected $submissionExporter;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_admin_config_exporters_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->fileSystem = $container->get('file_system');
    $instance->exporterManager = $container->get('plugin.manager.webform.exporter');
    $instance->submissionExporter = $container->get('webform_submission.exporter');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('webform.settings');

    $form['export_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Export general settings'),
      '#open' => TRUE,
    ];
    $form['export_settings']['temp_directory'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Temporary directory'),
      '#description' => $this->t('A local file system path where temporary export files will be stored. This directory should be persistent between requests and should not be accessible over the web.'),
      '#required' => TRUE,
      '#default_value' => $config->get('export.temp_directory') ?: $this->fileSystem->getTempDirectory(),
    ];

    // Export.
    $form['export_default_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Export default settings'),
      '#description' => $this->t('Enter default export settings to be used by all webforms.'),
      '#open' => TRUE,
    ];

    $export_options = $config->get('export');
    $export_form_state = new FormState();
    $this->submissionExporter->buildExportOptionsForm($form['export_default_settings'], $export_form_state, $export_options);

    // (Excluded) Exporters.
    $form['exporter_types'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission exporters'),
      '#description' => $this->t('Select available submission exporters'),
      '#open' => TRUE,
    ];
    $form['exporter_types']['excluded_exporters'] = $this->buildExcludedPlugins(
      $this->exporterManager,
      $config->get('export.excluded_exporters') ?: [] ?: []
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Copied from: system_check_directory().
    $temp_directory = $form_state->getValue('temp_directory');
    if (!is_dir($temp_directory) && !$this->fileSystem->mkdir($temp_directory, NULL, TRUE)) {
      $form_state->setErrorByName('temp_directory', $this->t('The directory %directory does not exist and could not be created.', ['%directory' => $temp_directory]));
    }
    if (is_dir($temp_directory) && !is_writable($temp_directory) && !$this->fileSystem->chmod($temp_directory)) {
      $form_state->setErrorByName('temp_directory', $this->t('The directory %directory exists but is not writable and could not be made writable.', ['%directory' => $temp_directory]));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $excluded_exporters = $this->convertIncludedToExcludedPluginIds($this->exporterManager, $form_state->getValue('excluded_exporters'));

    $values = $form_state->getValues();

    $export = $this->submissionExporter->getValuesFromInput($values) + ['excluded_exporters' => $excluded_exporters];

    // Set custom temp directory.
    $export['temp_directory'] = ($values['temp_directory'] === $this->fileSystem->getTempDirectory()) ? '' : $values['temp_directory'];

    // Update config and submit form.
    $config = $this->config('webform.settings');
    $config->set('export', $export);
    parent::submitForm($form, $form_state);
  }

}
