<?php

namespace Drupal\webform_submission_export_import\Plugin\WebformExporter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformExporter\FileHandleTraitWebformExporter;
use Drupal\webform\Plugin\WebformExporterBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a machine readable CSV export that can be imported back into the current webform.
 *
 * @WebformExporter(
 *   id = "webform_submission_export_import",
 *   label = @Translation("CSV download"),
 *   description = @Translation("Exports results in CSV that can be imported back into the current webform."),
 *   archive = FALSE,
 *   files = FALSE,
 *   options = FALSE,
 * )
 */
class WebformSubmissionExportImportWebformExporter extends WebformExporterBase {

  use FileHandleTraitWebformExporter;

  /**
   * Webform submission export importer service.
   *
   * @var \Drupal\webform_submission_export_import\WebformSubmissionExportImportImporterInterface
   */
  protected $importer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->importer = $container->get('webform_submission_export_import.importer');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'uuid' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $t_args = [
      '%type' => $this->label(),
      ':injection_href' => 'https://www.google.com/search?q=spreadsheet+formula+injection',
      ':excel_href' => 'https://www.drupal.org/project/webform_xlsx_export',
    ];
    $form['warning'] = [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t('<strong>Warning:</strong> Opening %type files with spreadsheet applications may expose you to <a href=":injection_href">formula injection</a> or other security vulnerabilities. When the submissions contain data from untrusted users and the downloaded file will be used with Microsoft Excel, use the <a href=":excel_href">Webform XLSX export</a> module.', $t_args),
    ];
    $form['uuid'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use UUIDs for all entity references'),
      '#description' => $this->t("If checked, all entity references will use the entity's UUID"),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['uuid'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFileExtension() {
    return 'csv';
  }

  /**
   * {@inheritdoc}
   */
  public function writeHeader() {
    $header = $this->getImporter()->exportHeader();
    fputcsv($this->fileHandle, $header);
  }

  /**
   * {@inheritdoc}
   */
  public function writeSubmission(WebformSubmissionInterface $webform_submission) {
    $record = $this->getImporter()->exportSubmission($webform_submission, $this->configuration);
    fputcsv($this->fileHandle, $record);
  }

  /**
   * Get the submission importer.
   *
   * @return \Drupal\webform_submission_export_import\WebformSubmissionExportImportImporterInterface
   *   The submission importer.
   */
  protected function getImporter() {
    $this->importer->setWebform($this->getWebform());
    return $this->importer;
  }

}
