<?php

namespace Drupal\az_migration\Plugin\QueueWorker;

use Devanych\Mime\MimeTypes;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\media\Entity\Media;
use Drupal\Core\File\FileSystemInterface;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Queue worker to lazily download media from integrations.
 *
 * @QueueWorker(
 *   id = "az_deferred_media",
 *   title = @Translation("Quickstart Deferred Media"),
 *   cron = {
 *     "time" = 30,
 *   },
 * )
 */
class DeferredMedia extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  // Regex for parsing Content-Disposition.
  const REQUEST_HEADER_FILENAME_REGEX = '@\bfilename(?<star>\*?)=\"(?<filename>.+)\"@';

  // Accept string for remote GET request.
  const REQUEST_ACCEPT = 'image/avif,image/webp,image/png,image/*';

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * @var \Drupal\file\FileRepository
   */
  protected $fileRepository;

  /**
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $pluginManagerMigration;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->fileRepository = $container->get('file.repository');
    $instance->fileSystem = $container->get('file_system');
    $instance->httpClient = $container->get('http_client');
    $instance->logger = $container->get('logger.factory')->get('az_migration');
    $instance->pluginManagerMigration = $container->get('plugin.manager.migration');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function deferredMigration($migration_id, $data) {
    $this->logger->notice(print_r($data, TRUE));
    try {
      $migration = $this->pluginManagerMigration->createInstance($migration_id);
    }
    catch (\Exception $e) {
      $this->logger->notice("Error during deferred migration @id: @msg", [
        '@id' => $migration_id,
        '@msg' => $e->getMessage(),
      ]);
      return;
    }
    // Get migration components.
    $destination = $migration->getDestinationPlugin(FALSE);
    $process = $migration->getProcess();
    $id_map = $migration->getIdMap();
    $executable = new MigrateExecutable($migration);

    // Get our original IDs from the map, if available.
    $ids = $migration->getSourcePlugin()->getIds();
    $source_ids = array_intersect_key($data, $ids);
    $entity_ids = reset($id_map->lookupDestinationIds($source_ids));
    if (!$entity_ids) {
      $entity_ids = [];
    }

    // Process a row through the migration.
    $row = new Row($data + $migration->getSourceConfiguration(), $ids, FALSE);
    $executable->processRow($row, $process);
    $destination_ids = [];

    try {
      // See if we can successfully import the row.
      $destination_ids = $destination->import($row, $entity_ids);
    }
    catch (\Exception $e) {
      // Write exceptions to the log.
      $id_map->saveMessage($row->getSourceIdValues(), $e->getMessage());
    }
    if ($destination_ids) {
      // Update the migrate map.
      $id_map->saveIdMapping($row, $destination_ids, MigrateIdMapInterface::STATUS_NEEDS_UPDATE);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $url = $data['url'] ?? NULL;
    $migrations = $data['migrations'] ?? [];
    // Bail out if we weren't given a URL.
    if (empty($url)) {
      return;
    }
    try {
      // Request the remote resource.
      $response = $this->httpClient->get($url, [
        // Specify an accept header to indicate what we're interested in.
        'headers'        => ['Accept' => static::REQUEST_ACCEPT],
      ]);
    }
    catch (\Exception $e) {
      $this->logger->notice("During deferred fetch of %url, %msg", [
        '%url' => $url,
        '%msg' => $e->getMessage(),
      ]);
      return;
    }
    // Get headers relevant to file.
    $type = $response->getHeader('Content-Type') ?? [];
    $type = array_pop($type);
    if (empty($type)) {
      $this->logger->notice("No Content-Type provided by %url", [
        '%url' => $url,
      ]);
      return;
    }
    // Fallback filename.
    $filename = 'remote_media';
    $mimeTypes = new MimeTypes();
    // Find the proper fallback file extension if possible.
    $extensions = $mimeTypes->getExtensions($type);
    if (!empty($extensions)) {
      $extension = reset($extensions);
      $filename .= '.' . $extension;
    }
    $disposition = $response->getHeader('Content-Disposition') ?? [];
    $disposition = array_pop($disposition);
    // See if we can determine the real remote filename from the disposition.
    if (!empty($disposition)) {
      preg_match(static::REQUEST_HEADER_FILENAME_REGEX, $disposition, $matches);
      if (!empty($matches['filename'])) {
        $filename = $matches['filename'];
      }
    }

    // Execute deferrred migrations for item.
    $deferred = $data['deferred'] ?? [];
    $body = $response->getBody();
    $destination = $data['path'] ?? 'public://';
    $this->fileSystem->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    $destination .= '/' . $filename;
    $uri = $this->fileSystem->saveData($body, $destination);
    foreach ($deferred as $migration => $row) {
      // Append emphemeral values discovered at the time of the GET request.
      $row['filename'] = $filename;
      $row['filemime'] = $type;
      $row['uri'] = $uri;
      // Run the migration for this item.
      $this->deferredMigration($migration, $row);
    }
  }

}
