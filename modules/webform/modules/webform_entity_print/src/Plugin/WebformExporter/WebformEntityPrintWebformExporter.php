<?php

namespace Drupal\webform_entity_print\Plugin\WebformExporter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformExporter\DocumentBaseWebformExporter;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a Webform Entity Print PDF exporter.
 *
 * @WebformExporter(
 *   id = "webform_entity_print",
 *   archive = TRUE,
 *   options = FALSE,
 *   deriver = "Drupal\webform_entity_print\Plugin\Derivative\WebformEntityPrintWebformExporterDeriver",
 * )
 */
class WebformEntityPrintWebformExporter extends DocumentBaseWebformExporter {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The plugin manager for our Print engines.
   *
   * @var \Drupal\entity_print\Plugin\EntityPrintPluginManagerInterface
   */
  protected $printEngineManager;

  /**
   * The export type manager.
   *
   * @var \Drupal\entity_print\Plugin\ExportTypeManagerInterface
   */
  protected $exportTypeManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The Print builder.
   *
   * @var \Drupal\entity_print\PrintBuilderInterface
   */
  protected $printBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->request = $container->get('request_stack')->getCurrentRequest();
    $instance->printEngineManager = $container->get('plugin.manager.entity_print.print_engine');
    $instance->exportTypeManager = $container->get('plugin.manager.entity_print.export_type');
    $instance->printBuilder = $container->get('entity_print.print_builder');
    $instance->fileSystem = $container->get('file_system');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'view_mode' => 'html',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('View mode'),
      '#options' => [
        'html' => $this->t('HTML'),
        'table' => $this->t('Table'),
      ],
      '#required' => TRUE,
      '#default_value' => $this->configuration['view_mode'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function writeSubmission(WebformSubmissionInterface $webform_submission) {
    $configuration = $this->getConfiguration();

    // Make sure Webform Entity Print template is used.
    // @see webform_entity_print_entity_view_alter()
    $this->request->request->set('_webform_entity_print', TRUE);

    // Set view mode.
    // @see \Drupal\webform\WebformSubmissionViewBuilder::view
    $this->request->request->set('_webform_submissions_view_mode', $configuration['view_mode']);

    // Get print engine.
    $export_type_id = $this->getExportTypeId();
    $print_engine = $this->printEngineManager->createSelectedInstance($export_type_id);

    // Get scheme.
    $scheme = 'temporary';

    // Get file name.
    $file_extension = $this->getExportTypeFileExtension();
    $file_name = $this->getSubmissionBaseName($webform_submission) . '.' . $file_extension;

    // Save printable document.
    $temporary_file_path = $this->printBuilder->savePrintable([$webform_submission], $print_engine, $scheme, $file_name);
    if ($temporary_file_path) {
      $this->addToArchive(file_get_contents($temporary_file_path), $file_name);
      $this->fileSystem->delete($temporary_file_path);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getBatchLimit() {
    // Limit batch document export to 10 submissions.
    return 10;
  }

  /* ************************************************************************ */
  // Export type methods.
  /* ************************************************************************ */

  /**
   * Get export type id.
   *
   * @return string
   *   The export type id.
   */
  protected function getExportTypeId() {
    return str_replace('webform_entity_print:', '', $this->getPluginId());
  }

  /**
   * Get export type definition.
   *
   * @return array
   *   Export type definition.
   */
  protected function getExportTypeDefinition() {
    $export_type_id = $this->getExportTypeId();
    return $this->exportTypeManager->getDefinition($export_type_id);
  }

  /**
   * Get export type file extension.
   *
   * @return string
   *   Export type file extension.
   */
  protected function getExportTypeFileExtension() {
    $definition = $this->getExportTypeDefinition();
    return $definition['file_extension'];
  }

}
