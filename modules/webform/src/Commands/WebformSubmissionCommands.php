<?php

namespace Drupal\webform\Commands;

use Consolidation\AnnotatedCommand\AnnotationData;
use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform\Controller\WebformResultsExportController;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Form\WebformResultsClearForm;
use Drupal\webform\Form\WebformSubmissionsPurgeForm;
use Drupal\webform\WebformSubmissionExporterInterface;
use Drupal\webform_submission_export_import\Form\WebformSubmissionExportImportUploadForm;
use Drush\Drush;
use Drush\Exceptions\UserAbortException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Webform submission related commands for Drush 9.x and 10.x.
 */
class WebformSubmissionCommands extends WebformCommandsBase {

  use StringTranslationTrait;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The webform submission export service.
   *
   * @var \Drupal\webform\WebformSubmissionExporterInterface
   */
  protected $submissionExporter;

  /**
   * WebformSubmissionCommands constructor.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   File system service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\webform\WebformSubmissionExporterInterface $submission_exporter
   *   The webform submission export service.
   */
  public function __construct(FileSystemInterface $file_system, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, WebformSubmissionExporterInterface $submission_exporter) {
    parent::__construct();
    $this->fileSystem = $file_system;
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->submissionExporter = $submission_exporter;
  }

  /* ************************************************************************ */
  // Export.
  /* ************************************************************************ */

  /**
   * Allow users to choose the webform to be exported.
   *
   * @hook interact webform:export
   */
  public function exportInteract(InputInterface $input, OutputInterface $output, AnnotationData $annotationData) {
    $webform = $input->getArgument('webform');
    if (!$webform) {
      $webforms = array_keys(Webform::loadMultiple());
      $choices = array_combine($webforms, $webforms);
      $choice = $this->io()->choice(dt("Choose a webform to export submissions from."), $choices);
      $input->setArgument('webform', $choice);
    }
  }

  /**
   * Validates the webform to be exported.
   *
   * @hook validate webform:export
   */
  public function exportValidate(CommandData $commandData) {
    $arguments = $commandData->getArgsWithoutAppName();
    $webform = $arguments['webform'] ?? NULL;
    if ($webform) {
      $this->validateWebform($webform);
    }
  }

  /**
   * Exports webform submissions to a file.
   *
   * @param string $webform
   *   The webform ID you want to export (required unless --entity-type and
   *   --entity-id are specified)
   * @param array $options
   *   (optional) An array of options.
   *
   * @command webform:export
   *
   * @option exporter The type of export. (delimited, table, yaml, or json)
   * @option delimiter Delimiter between columns (defaults to site-wide setting). This option may need to be wrapped in quotes. i.e. --delimiter="\t".
   * @option multiple-delimiter Delimiter between an element with multiple values (defaults to site-wide setting).
   * @option file-name File name used to export submission and uploaded filed. You may use tokens.
   * @option archive-type Archive file type for submission file uploadeds and generated records. (tar or zip)
   * @option header-format Set to "label" (default) or "key"
   * @option options-item-format Set to "label" (default) or "key". Set to "key" to print select list values by their keys instead of labels.
   * @option options-single-format Set to "separate" (default) or "compact" to determine how single select list values are exported.
   * @option options-multiple-format Set to "separate" (default) or "compact" to determine how multiple select list values are exported.
   * @option entity-reference-items Comma-separated list of entity reference items (id, title, and/or url) to be exported.
   * @option excluded-columns Comma-separated list of component IDs or webform keys to exclude.
   * @option uuid  Use UUIDs for all entity references. (Only applies to CSV download)
   * @option entity-type The entity type to which this submission was submitted from.
   * @option entity-id The ID of the entity of which this webform submission was submitted from.
   * @option range-type Range of submissions to export: "all", "latest", "serial", "sid", or "date".
   * @option range-latest Integer specifying the latest X submissions will be downloaded. Used if "range-type" is "latest" or no other range options are provided.
   * @option range-start The submission ID or start date at which to start exporting.
   * @option range-end The submission ID or end date at which to end exporting.
   * @option uid The ID of the user who submitted the form.
   * @option langcode The language code of the submission.
   * @option order The submission order "asc" (default) or "desc".
   * @option state Submission state to be included: "completed", "draft" or "all" (default).
   * @option sticky Flagged/starred submission status.
   * @option files Download files: "1" or "0" (default). If set to 1, the exported CSV file and any submission file uploads will be download in a gzipped tar file.
   * @option destination The full path and filename in which the CSV or archive should be stored. If omitted the CSV file or archive will be outputted to the command line.
   *
   * @aliases wfx,webform-export
   */
  public function export($webform = NULL, array $options = ['exporter' => NULL, 'delimiter' => NULL, 'multiple-delimiter' => NULL, 'file-name' => NULL, 'archive-type' => NULL, 'header-format' => NULL, 'options-item-format' => NULL, 'options-single-format' => NULL, 'options-multiple-format' => NULL, 'entity-reference-items' => NULL, 'excluded-columns' => NULL, 'uuid' => NULL, 'entity-type' => NULL, 'entity-id' => NULL, 'range-type' => NULL, 'range-latest' => NULL, 'range-start' => NULL, 'range-end' => NULL, 'uid' => NULL, 'langcode' => NULL, 'order' => NULL, 'state' => NULL, 'sticky' => NULL, 'files' => NULL, 'destination' => NULL]) {
    $webform = Webform::load($webform);
    // @todd Determine if we should get source entity from options entity type
    // and id.
    $source_entity = NULL;

    $submission_exporter = $this->submissionExporter;
    $submission_exporter->setWebform($webform);
    $submission_exporter->setSourceEntity($source_entity);

    // Get command options as export options.
    $default_options = $submission_exporter->getDefaultExportOptions();
    $export_options = Drush::redispatchOptions();
    $export_options['access_check'] = FALSE;
    // Convert dashes to underscores.
    foreach ($export_options as $key => $value) {
      unset($export_options[$key]);
      $key = str_replace('-', '_', $key);
      if (isset($default_options[$key]) && is_array($default_options[$key])) {
        $value = explode(',', $value);
        $value = array_combine($value, $value);
      }
      $export_options[$key] = $value;
    }
    $submission_exporter->setExporter($export_options);

    WebformResultsExportController::batchSet($webform, $source_entity, $export_options);
    drush_backend_batch_process();

    $file_path = ($submission_exporter->isArchive()) ? $submission_exporter->getArchiveFilePath() : $submission_exporter->getExportFilePath();
    if (isset($export_options['destination'])) {
      $this->output()->writeln(dt('Created @destination', ['@destination' => $export_options['destination']]));
      $this->fileSystem->copy($file_path, $export_options['destination'], FileSystemInterface::EXISTS_REPLACE);
    }
    else {
      $this->output()->writeln(file_get_contents($file_path));
    }
    @unlink($file_path);

    return NULL;
  }

  /* ************************************************************************ */
  // Import.
  /* ************************************************************************ */

  /**
   * Validate webform and target URI to be imported.
   *
   * @hook validate webform:import
   */
  public function importValidate(CommandData $commandData) {
    if (!$this->moduleHandler->moduleExists('webform_submission_export_import')) {
      throw new \Exception(dt('The Webform Submission Export/Import module must be enabled to perform imports.'));
    }

    $this->validateWebform();

    $arguments = $commandData->getArgsWithoutAppName();
    $import_uri = $arguments['import_uri'] ?? NULL;
    if (empty($import_uri)) {
      throw new \Exception(dt('Please include the CSV path or URI.'));
    }
  }

  /**
   * Imports webform submissions from a CSV file.
   *
   * @param string $webform
   *   The webform ID you want to import (required unless --entity-type
   *   and --entity-id are specified)
   * @param string $import_uri
   *   The path or URI for the CSV file to be imported.
   * @param array $options
   *   (optional) An array of options.
   *
   * @command webform:import
   *
   * @option skip_validation Skip form validation.
   * @option treat_warnings_as_errors Treat all warnings as errors.
   * @option entity-type The entity type to which this submission was submitted from.
   * @option entity-id The ID of the entity of which this webform submission was submitted from.
   *
   * @aliases wfi,webform-import
   */
  public function import($webform = NULL, $import_uri = NULL, array $options = ['skip_validation' => NULL, 'treat_warnings_as_errors' => NULL, 'entity-type' => NULL, 'entity-id' => NULL]) {
    /** @var \Drupal\webform_submission_export_import\WebformSubmissionExportImportImporterInterface $submission_importer */
    // phpcs:ignore
    $submission_importer = \Drupal::service('webform_submission_export_import.importer');

    // Get webform.
    $webform = Webform::load($webform);

    // Get source entity.
    $entity_type = $this->input()->getOption('entity-type');
    $entity_id = $this->input()->getOption('entity-id');
    if ($entity_type && $entity_id) {
      $source_entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
    }
    else {
      $source_entity = NULL;
    }

    // Get import options.
    $import_options = $options
      + $submission_importer->getDefaultImportOptions();

    $submission_importer->setWebform($webform);
    $submission_importer->setSourceEntity($source_entity);
    $submission_importer->setImportOptions($import_options);
    $submission_importer->setImportUri($import_uri);
    $t_args = ['@total' => $submission_importer->getTotal()];
    if (!$this->io()->confirm(dt('Are you sure you want to import @total submissions?', $t_args) . PHP_EOL . dt('This action cannot be undone.'))) {
      throw new UserAbortException();
    }

    WebformSubmissionExportImportUploadForm::batchSet($webform, $source_entity, $import_uri, $import_options);
    drush_backend_batch_process();

    return NULL;

  }

  /* ************************************************************************ */
  // Purge.
  /* ************************************************************************ */

  /**
   * Allow users to choose the webform to be purged.
   *
   * @hook interact webform:purge
   */
  public function purgeInteract(InputInterface $input, OutputInterface $output, AnnotationData $annotationData) {
    $all = $input->getOption('all');
    $webform = $input->getArgument('webform');
    if (!$webform && !$all) {
      $webforms = array_keys(Webform::loadMultiple());
      $choices = array_combine($webforms, $webforms);
      $choice = $this->io()->choice(dt("Choose a webform to purge submissions from."), $choices);
      $input->setArgument('webform', $choice);
    }
  }

  /**
   * Validate the webform to be purged.
   *
   * @hook validate webform:purge
   */
  public function purgeValidate(CommandData $commandData) {
    $arguments = $commandData->getArgsWithoutAppName();
    $webform = $arguments['webform'] ?? NULL;

    $options = $commandData->options();
    $all = $options['all'] ?? NULL;

    // If webform id is set to 'all' or not included skip validation.
    if ($all || $webform === NULL) {
      return;
    }

    $this->validateWebform($webform);
  }

  /**
   * Purge webform submissions from the databases.
   *
   * @param string $webform
   *   A webform machine name. If not provided, user may choose from a
   *   list of names.
   * @param array $options
   *   (optional) An array of options.
   *
   * @command webform:purge
   *
   * @option all Flush all submissions
   * @option entity-type The entity type for webform submissions to be purged
   * @option entity-id The ID of the entity for webform submissions to be purged
   *
   * @usage drush webform:purge
   *   Pick a webform and then purge its submissions.
   * @usage drush webform:purge contact
   *   Delete 'Contact' webform submissions.
   * @usage drush webform:purge ::all
   *   Purge all webform submissions.
   *
   * @aliases wfp,webform-purge
   */
  public function purge($webform = NULL, array $options = ['all' => FALSE, 'entity-type' => NULL, 'entity-id' => NULL]) {
    if ($options['all']) {
      $webform = 'all';
    }

    // Set the webform.
    $webform = ($webform === 'all') ? NULL : Webform::load($webform);

    /** @var \Drupal\webform\WebformEntityStorageInterface $webform_storage */
    $webform_storage = $this->entityTypeManager->getStorage('webform');
    /** @var \Drupal\webform\WebformSubmissionStorageInterface $submission_storage */
    $submission_storage = $this->entityTypeManager->getStorage('webform_submission');

    // Make sure there are submissions that need to be deleted.
    if (!$submission_storage->getTotal($webform)) {
      $this->output()->writeln(dt('There are no submissions that need to be deleted.'));
      return;
    }

    if (!$webform) {
      $submission_total = $submission_storage->getQuery()->count()->accessCheck(FALSE)->execute();
      $form_total = $webform_storage->getQuery()->count()->accessCheck(FALSE)->execute();

      $t_args = [
        '@submission_total' => $submission_total,
        '@submissions' => $this->formatPlural($submission_total, 'submission', 'submissions'),
        '@form_total' => $form_total,
        '@forms' => $this->formatPlural($form_total, 'webform', 'webforms'),
      ];
      if (!$this->io()->confirm(dt('Are you sure you want to delete @submission_total @submissions in @form_total @forms?', $t_args))) {
        throw new UserAbortException();
      }

      // phpcs:ignore
      $form = WebformResultsClearForm::create(\Drupal::getContainer());
      $form->batchSet();
      drush_backend_batch_process();
    }
    else {
      // Set source entity.
      $entity_type = $this->input()->getOption('entity-type');
      $entity_id = $this->input()->getOption('entity-id');
      $source_entity = ($entity_type && $entity_id) ? $this->entityTypeManager->getStorage($entity_type)->load($entity_id) : NULL;

      $t_args = [
        '@title' => ($source_entity) ? $source_entity->label() : $webform->label(),
      ];
      if (!$this->io()->confirm(dt("Are you sure you want to delete all submissions from '@title' webform?", $t_args))) {
        throw new UserAbortException();
      }

      // phpcs:ignore
      $form = WebformSubmissionsPurgeForm::create(\Drupal::getContainer());
      $form->batchSet($webform, $source_entity);
      drush_backend_batch_process();
    }
  }

  /* ************************************************************************ */
  // Generate.
  /* ************************************************************************ */

  /**
   * Validates the webform to have submissions generated.
   *
   * @hook validate webform:generate
   */
  public function generateValidate(CommandData $commandData) {
    $this->validateWebform();
  }

  /**
   * Create submissions in specified webform.
   *
   * @param string $webform
   *   Webform id into which new submissions will be inserted.
   * @param int $num
   *   Number of submissions to insert. Defaults to 50.
   * @param array $options
   *   (optional) An array of options.
   *
   * @command webform:generate
   *
   * @option kill Delete all submissions in specified webform before generating.
   * @option feedback An integer representing interval for insertion rate logging. Defaults to 1000
   * @option entity-type The entity type to which this submission was submitted from.
   * @option entity-id The ID of the entity of which this webform submission was submitted from.
   *
   * @aliases wfg,webform-generate
   */
  public function generate($webform = NULL, $num = NULL, array $options = ['kill' => FALSE, 'feedback' => 1000, 'entity-type' => NULL, 'entity-id' => NULL]) {
    $values = [
      'webform_ids' => [$webform => $webform],
      'num' => $num ?: 50,
    ] + $options;
    /** @var \Drupal\webform\Plugin\DevelGenerate\WebformSubmissionDevelGenerate $instance */
    // phpcs:ignore
    $instance = \Drupal::service('plugin.manager.develgenerate')->createInstance('webform_submission', []);
    $instance->generate($values);
  }

}
