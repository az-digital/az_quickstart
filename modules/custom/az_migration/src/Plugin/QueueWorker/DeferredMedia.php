<?php

namespace Drupal\az_migration\Plugin\QueueWorker;

use Devanych\Mime\MimeTypes;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\File\Event\FileUploadSanitizeNameEvent;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileExists;
use Drupal\file\Plugin\migrate\destination\EntityFile;
use Drupal\migrate\MigrateExecutable;
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
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

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
    $instance->eventDispatcher = $container->get('event_dispatcher');
    $instance->fileSystem = $container->get('file_system');
    $instance->httpClient = $container->get('http_client');
    $instance->logger = $container->get('logger.factory')->get('az_migration');
    $instance->pluginManagerMigration = $container->get('plugin.manager.migration');
    return $instance;
  }

  /**
   * Processes a deferred migration for a single item.
   *
   * This function processes a migration with data found after
   * a deferred GET fetch of the remote URL completes. Only the
   * single row being processed by this queue is imported. If
   * the migration is found, the remote file will be hashed and
   * checked against the hash of the existing file's URI to
   * determine if it should be updated.
   *
   * @param string $migration_id
   *   The migration plugin id to processe.
   * @param array $data
   *   The individual row to process, in array format.
   * @param string $body
   *   The bytes of the remote file.
   */
  public function deferredMigration($migration_id, $data, $body) {
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

    // For file migrations, we should hash existing file to see if it's changed.
    if (($destination instanceof EntityFile) && (!empty($entity_ids))) {
      $file = $this->entityTypeManager->getStorage('file')->load(reset($entity_ids));
      if (!empty($file)) {
        $orig_uri = $file->getFileUri();
        if ($orig_uri && file_exists($orig_uri)) {
          $data['uri'] = $orig_uri;
          $hash = Crypt::hashBase64($body);
          $orig_hash = Crypt::hashBase64(file_get_contents($orig_uri));
          // File is changed. Update uri.
          if ($hash !== $orig_hash) {
            $uri = $this->fileSystem->createFilename($data['filename'], $data['directory']);
            $uri = $this->fileSystem->saveData($body, $uri, FileExists::Replace);
            if ($uri !== FALSE) {
              $data['uri'] = $uri;
            }
            $this->logger->notice("Updating @orig_uri to @uri because the hash changed from @orig_hash to @hash", [
              '@orig_uri' => $orig_uri,
              '@uri' => $uri,
              '@orig_hash' => $orig_hash,
              '@hash' => $hash,
            ]);
          }
        }
      }
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
      $this->logger->notice("DEBUG: Performing deferred fetch of %url", [
        '%url' => $url,
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
    $this->logger->notice("DEBUG: detected content type %type", [
      '%type' => $type,
    ]);
    // Fallback filename.
    $filename = 'remote_media';
    $mimeTypes = new MimeTypes();
    // Find the proper fallback file extension if possible.
    $extensions = $mimeTypes->getExtensions($type);
    $extension = '';
    if (!empty($extensions)) {
      $extension = reset($extensions);
      $filename .= '.' . $extension;
    }
    else {
      $this->logger->notice("Couldn't determine file extension for %url", [
        '%url' => $url,
      ]);
      return;
    }
    $this->logger->notice("DEBUG: recognized extension as %ext", [
      '%ext' => $extension,
    ]);
    $disposition = $response->getHeader('Content-Disposition') ?? [];
    $disposition = array_pop($disposition);
    // See if we can determine the real remote filename from the disposition.
    if (!empty($disposition)) {
      preg_match(static::REQUEST_HEADER_FILENAME_REGEX, $disposition, $matches);
      if (!empty($matches['filename'])) {
        $filename = $matches['filename'];
      }
    }
    $this->logger->notice("DEBUG: determined filename as %filename", [
      '%filename' => $filename,
    ]);

    // Prepare some variables for migration.
    $deferred = $data['deferred'] ?? [];
    $body = $response->getBody();
    $directory = $data['path'] ?? 'public://';

    // Make sure our destination directory exists.
    $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

    // Sanitize our filename. Give other modules a change to weigh in.
    $event = new FileUploadSanitizeNameEvent($filename, $extension);
    $this->eventDispatcher->dispatch($event);
    $filename = $event->getFilename();
    $this->logger->notice("DEBUG: sanitized to %filename", [
      '%filename' => $filename,
    ]);

    // Execute deferrred migrations for item.
    foreach ($deferred as $migration => $row) {
      // Append emphemeral values discovered at the time of the GET request.
      $row['filename'] = $filename;
      $row['directory'] = $directory;
      $row['filemime'] = $type;
      // Run the migration for this item.
      $this->deferredMigration($migration, $row, $body);
    }
  }

}
