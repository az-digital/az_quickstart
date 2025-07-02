<?php

namespace Drupal\webform_submission_export_import\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\webform\EntityStorage\WebformEntityStorageTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Provides route responses for webform submission export/import.
 */
class WebformSubmissionExportImportController extends ControllerBase implements ContainerInjectionInterface {

  use WebformEntityStorageTrait;

  /**
   * The webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * The webform submission generation service.
   *
   * @var \Drupal\webform\WebformSubmissionGenerateInterface
   */
  protected $generate;

  /**
   * The webform submission exporter.
   *
   * @var \Drupal\webform_submission_export_import\WebformSubmissionExportImportImporterInterface
   */
  protected $importer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\webform_submission_export_import\Controller\WebformSubmissionExportImportController $instance */
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->requestHandler = $container->get('webform.request');
    $instance->generate = $container->get('webform_submission.generate');
    $instance->importer = $container->get('webform_submission_export_import.importer');
    $instance->initialize();
    return $instance;
  }

  /**
   * Initialize WebformSubmissionExportImportController object.
   */
  protected function initialize() {
    $webform = $this->requestHandler->getCurrentWebform();
    $source_entity = $this->requestHandler->getCurrentSourceEntity();
    $this->importer->setWebform($webform);
    $this->importer->setSourceEntity($source_entity);
  }

  /**
   * Returns the Webform submission export example CSV view.
   */
  public function view() {
    return $this->createResponse(FALSE);
  }

  /**
   * Returns the Webform submission export example CSV download.
   */
  public function download() {
    return $this->createResponse(TRUE);
  }

  /**
   * Create a response containing submission CSV example.
   *
   * @param bool $download
   *   TRUE is response should be downloaded.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response containing submission CSV example.
   */
  protected function createResponse($download = FALSE) {
    $webform = $this->importer->getWebform();

    // From: http://obtao.com/blog/2013/12/export-data-to-a-csv-file-with-symfony/
    $response = new StreamedResponse(function () {
      $handle = fopen('php://output', 'r+');

      $header = $this->importer->exportHeader();
      fputcsv($handle, $header);

      for ($i = 1; $i <= 3; $i++) {
        $webform_submission = $this->generateSubmission($i);
        $record = $this->importer->exportSubmission($webform_submission);
        fputcsv($handle, $record);
      }

      fclose($handle);
    });

    $response->headers->set('Content-Type', $download ? 'text/csv' : 'text/plain');
    $response->headers->set('Content-Disposition', ($download ? 'attachment' : 'inline') . '; filename=' . $webform->id() . '.csv');
    return $response;
  }

  /**
   * Generate an unsaved webform submission.
   *
   * @param int $index
   *   The submission's index used for the sid and serial number.
   *
   * @return \Drupal\webform\WebformSubmissionInterface
   *   An unsaved webform submission.
   */
  protected function generateSubmission($index) {
    $webform = $this->requestHandler->getCurrentWebform();
    $source_entity = $this->requestHandler->getCurrentSourceEntity();

    $users = $this->getEntityStorage('user')->getQuery()->accessCheck(TRUE)->execute();
    $uid = array_rand($users);

    $url = $webform->toUrl();
    if ($source_entity && $source_entity->hasLinkTemplate('canonical')) {
      $url = $source_entity->toUrl();
    }

    return $this->getSubmissionStorage()->create([
      'sid' => $index,
      'serial' => $index,
      'webform_id' => $webform->id(),
      'entity_type' => ($source_entity) ? $source_entity->getEntityTypeId() : '',
      'entity_id' => ($source_entity) ? $source_entity->id() : '',
      'uid' => $uid,
      'remote_addr' => mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255),
      'uri' => preg_replace('#^' . base_path() . '#', '/', $url->toString()),
      'data' => Yaml::encode($this->generate->getData($webform)),
      'created' => strtotime('-1 year'),
      'completed' => rand(strtotime('-1 year'), time()),
      'changed' => time(),
    ]);
  }

}
