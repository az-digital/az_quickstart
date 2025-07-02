<?php

namespace Drupal\webform\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\webform\EntityStorage\WebformEntityStorageTrait;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for a results exporter.
 *
 * @see \Drupal\webform\Plugin\WebformExporterInterface
 * @see \Drupal\webform\Plugin\WebformExporterManager
 * @see \Drupal\webform\Plugin\WebformExporterManagerInterface
 * @see plugin_api
 */
abstract class WebformExporterBase extends PluginBase implements WebformExporterInterface {

  use WebformEntityStorageTrait;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * Cached archive object.
   *
   * @var \Archive_Tar|\ZipArchive
   */
  protected $archive;

  /**
   * The configuration array.
   *
   * @var array
   */
  protected $configuration;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);

    $instance->logger = $container->get('logger.factory')->get('webform');
    $instance->configFactory = $container->get('config.factory');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->elementManager = $container->get('plugin.manager.webform.element');
    $instance->tokenManager = $container->get('webform.token_manager');

    $instance->setConfiguration($configuration);

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function description() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isExcluded() {
    return $this->configFactory->get('webform.settings')->get('export.excluded_exporters.' . $this->pluginDefinition['id']) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isArchive() {
    return $this->pluginDefinition['archive'];
  }

  /**
   * {@inheritdoc}
   */
  public function hasFiles() {
    return $this->pluginDefinition['files'];
  }

  /**
   * {@inheritdoc}
   */
  public function hasOptions() {
    return $this->pluginDefinition['options'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'webform' => NULL,
      'source_entity' => NULL,
    ];
  }

  /**
   * Get the webform whose submissions are being exported.
   *
   * @return \Drupal\webform\WebformInterface
   *   A webform.
   */
  protected function getWebform() {
    return $this->configuration['webform'];
  }

  /**
   * Get the webform source entity whose submissions are being exported.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A webform's source entity.
   */
  protected function getSourceEntity() {
    return $this->configuration['source_entity'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function createExport() {}

  /**
   * {@inheritdoc}
   */
  public function openExport() {}

  /**
   * {@inheritdoc}
   */
  public function closeExport() {}

  /**
   * {@inheritdoc}
   */
  public function writeHeader() {}

  /**
   * {@inheritdoc}
   */
  public function writeSubmission(WebformSubmissionInterface $webform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function writeFooter() {}

  /**
   * {@inheritdoc}
   */
  public function getFileTempDirectory() {
    return $this->configFactory->get('webform.settings')->get('export.temp_directory') ?: \Drupal::service('file_system')->getTempDirectory();
  }

  /**
   * {@inheritdoc}
   */
  public function getSubmissionBaseName(WebformSubmissionInterface $webform_submission) {
    $export_options = $this->getConfiguration();
    $file_name = $export_options['file_name'];
    $file_name = $this->tokenManager->replace($file_name, $webform_submission);

    // Sanitize file name.
    // @see http://stackoverflow.com/questions/2021624/string-sanitizer-for-filename
    $file_name = preg_replace('([^\w\s\d\-_~,;:\[\]\(\].]|[\.]{2,})', '', $file_name);
    $file_name = preg_replace('/\s+/', '-', $file_name);
    return $file_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getFileExtension() {
    return 'txt';
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFileName() {
    $webform = $this->getWebform();
    $source_entity = $this->getSourceEntity();
    if ($source_entity) {
      return $webform->id() . '.' . $source_entity->getEntityTypeId() . '.' . $source_entity->id();
    }
    else {
      return $webform->id();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getExportFileName() {
    return $this->getBaseFileName() . '.' . $this->getFileExtension();
  }

  /**
   * {@inheritdoc}
   */
  public function getExportFilePath() {
    return $this->getFileTempDirectory() . '/' . $this->getExportFileName();
  }

  /**
   * {@inheritdoc}
   */
  public function getArchiveFilePath() {
    return $this->getFileTempDirectory() . '/' . $this->getArchiveFileName();
  }

  /**
   * {@inheritdoc}
   */
  public function getArchiveFileName() {
    return $this->getBaseFileName() . '.' . $this->getArchiveFileExtension();
  }

  /**
   * {@inheritdoc}
   */
  public function getArchiveType() {
    return ($this->configuration['archive_type'] === WebformExporterInterface::ARCHIVE_ZIP
      && class_exists('\ZipArchive'))
      ? WebformExporterInterface::ARCHIVE_ZIP
      : WebformExporterInterface::ARCHIVE_TAR;
  }

  /**
   * {@inheritdoc}
   */
  public function getArchiveFileExtension() {
    return ($this->getArchiveType() === WebformExporterInterface::ARCHIVE_ZIP)
      ? 'zip'
      : 'tar.gz';
  }

  /**
   * {@inheritdoc}
   */
  public function addToArchive($path, $name, array $options = []) {
    $options += [
      'remove_path' => '',
      'close' => FALSE,
    ];

    if ($this->getArchiveType() === WebformExporterInterface::ARCHIVE_ZIP) {
      $this->addToZipFile($path, $name, $options);
    }
    else {
      $this->addToTarArchive($path, $name, $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getBatchLimit() {
    return $this->configFactory->get('webform.settings')->get('batch.default_batch_export_size') ?: 500;
  }

  /* ************************************************************************ */
  // Archive helper methods.
  /* ************************************************************************ */

  /**
   * Add file, directory, or content to Tar archive.
   *
   * @param string $path
   *   System path or file content.
   * @param string $name
   *   Archive path or file name (applies to file content).
   * @param array $options
   *   Zip file options.
   */
  protected function addToTarArchive($path, $name, array $options = []) {
    if (!isset($this->archive)) {
      $this->archive = new \Archive_Tar($this->getArchiveFilePath(), 'gz');
    }

    if (@file_exists($path)) {
      if (is_dir($path)) {
        // Add directory to Tar archive.
        $this->archive->addModify((array) $path, $name, $options['remove_path']);
      }
      else {
        // Add file to Tar archive.
        $this->archive->addModify((array) $path, $name, $options['remove_path']);
      }
    }
    else {
      // Add text to Tar archive.
      $this->archive->addString($name, $path);
    }

    // Reset the Tar archive.
    // @see \Drupal\webform\WebformSubmissionExporter::writeExportToArchive
    if (!empty($options['close'])) {
      $this->archive = NULL;
    }
  }

  /**
   * Add file, directory, or content to ZIP file.
   *
   * @param string $path
   *   System path or file content.
   * @param string $name
   *   Archive path or file name (applies to file content).
   * @param array $options
   *   Zip file options.
   */
  protected function addToZipFile($path, $name, array $options = []) {
    if (!isset($this->archive)) {
      $this->archive = new \ZipArchive();
      $flags = !file_exists($this->getArchiveFilePath()) ? \ZipArchive::CREATE : 0;
      $this->archive->open($this->getArchiveFilePath(), $flags);
    }

    if (@file_exists($path)) {
      if (is_dir($path)) {
        // Add directory to ZIP file.
        $options += ['add_path' => $name . '/'];
        $this->archive->addPattern('/\.[a-zA-Z0-9]+$/', $path, $options);
      }
      else {
        // Add file to ZIP file.
        // Get file name from the path and remove path option.
        $file_name = $path;
        if ($options['remove_path']) {
          $file_name = preg_replace('#^' . $options['remove_path'] . '#', '', $file_name);
        }
        $file_name = ltrim($file_name, '/');
        $this->archive->addFile($path, $name . '/' . $file_name);
      }
    }
    else {
      // Add text to ZIP file.
      $this->archive->addFromString($name, $path);
    }

    // Close and reset the ZIP file.
    // @see \Drupal\webform\WebformSubmissionExporter::writeExportToArchive
    if (!empty($options['close'])) {
      $this->archive->close();
      $this->archive = NULL;
    }
  }

}
