<?php

namespace Drupal\az_migration_remote\Plugin\migrate\process;

use Devanych\Mime\MimeTypes;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\File\Event\FileUploadSanitizeNameEvent;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Provides uri to created file in the file system after a remote download.
 *
 * This process plugin fetches remote files and copies them to the drupal
 * filesystem. If an optional file migration and source_ids configuration
 * is added, the remote file will be hashed and compared to the original
 * file to determine if it is new.
 *
 * If the file is new or changed, it will be copied to the filesystem and
 * have its uri returned.
 *
 * If the file is unchanged or the fetch of the remote URL does not succeed
 * the original uri specificed by the migration setting will be returned.
 *
 * The intent of this behavior is to not update local copies of files
 * unnecessarily - only when they have changed.
 *
 * Available configuration keys
 * - migration: (optional) a file migration to look up existing files
 * - source_ids: (optional) an array of source field names.
 * - default_filename: Specify a default base filename. No file extension.
 * - directory: A stream and directory where new files should be placed.
 * - source: The URL of the remote file to download.
 *
 * @code
 * process:
 *   uri:
 *     plugin: az_migration_remote_file
 *     migration: az_trellis_events_files
 *     default_filename: 'trellis-event-image'
 *     directory: 'public://trellis-events'
 *     source_ids:
 *       - id
 *     source: image_url
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 */
#[MigrateProcess('az_migration_remote_file')]
class MigrationRemoteFile extends ProcessPluginBase implements ContainerFactoryPluginInterface {

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
   * The migrate lookup service.
   *
   * @var \Drupal\migrate\MigrateLookupInterface
   */
  protected $migrateLookup;

  /**
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $pluginManagerMigration;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
    );

    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->eventDispatcher = $container->get('event_dispatcher');
    $instance->fileSystem = $container->get('file_system');
    try {
      // Use the distribution cached http client if it is available.
      $instance->httpClient = $container->get('az_http.http_client');
    }
    catch (ServiceNotFoundException $e) {
      // Otherwise, fall back on the Drupal core guzzle client.
      $instance->httpClient = $container->get('http_client');
    }
    $instance->logger = $container->get('logger.factory')->get('az_migration_remote');
    $instance->migrateLookup = $container->get('migrate.lookup');
    $instance->pluginManagerMigration = $container->get('plugin.manager.migration');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // Url is provided as the value of the process plugin.
    $url = $value;
    if (is_array($url)) {
      $url = reset($url);
    }
    $original_uri = NULL;

    // Lookup the existing uri. The existing uri constitutes our default.
    // We will return this if unchanged or can't fetch the new file.
    $migration = $this->configuration['migration'] ?? NULL;
    $source_ids = $this->configuration['source_ids'] ?? [];
    $source_ids = $row->getMultiple($source_ids);
    if (!empty($migration) && !empty($source_ids)) {
      // Lookup the file migration to find the current file.
      try {
        $destination_id_array = $this->migrateLookup->lookup($migration, $source_ids);
      }
      catch (\Exception $e) {
        $destination_id_array = [];
      }
      $destination_id_array = reset($destination_id_array);
      if (!empty($destination_id_array)) {
        // Attempt to load the destination file entity and see if it has a uri.
        $file = $this->entityTypeManager->getStorage('file')->load(reset($destination_id_array));
        if (!empty($file)) {
          $original_uri = $file->getFileUri();
        }
      }
    }

    // There's nothing to fetch if we don't have an URL.
    if (empty($url)) {
      return $original_uri;
    }

    try {
      // Request the remote resource.
      $response = $this->httpClient->get($url, [
        // Specify an accept header to indicate what we're interested in.
        'headers'        => ['Accept' => static::REQUEST_ACCEPT],
      ]);
    }
    catch (\Exception $e) {
      $this->logger->notice("During fetch of %url, %msg", [
        '%url' => $url,
        '%msg' => $e->getMessage(),
      ]);
      return $original_uri;
    }
    // Get headers relevant to file.
    $type = $response->getHeader('Content-Type') ?? [];
    $type = array_pop($type);
    if (empty($type)) {
      $this->logger->notice("No Content-Type provided by %url", [
        '%url' => $url,
      ]);
      return $original_uri;
    }

    // Get the fallback filename.
    $filename = $row->get($this->configuration['default_filename']) ?? 'file';

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
    $this->logger->debug("Recognized extension as %ext", [
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
    $this->logger->debug("Determined filename as %filename", [
      '%filename' => $filename,
    ]);

    $body = $response->getBody();

    // Get directory path for new files.
    $directory = $this->configuration['directory'] ?? 'public://';

    // Sanitize our filename. Give other modules a chance to weigh in.
    $event = new FileUploadSanitizeNameEvent($filename, $extension);
    $this->eventDispatcher->dispatch($event);
    $filename = $event->getFilename();
    $this->logger->debug("Sanitized to %filename", [
      '%filename' => $filename,
    ]);

    // Check hash to determine whether to return this file or original.
    $original_hash = NULL;
    $uri = $original_uri;
    if ($original_uri && file_exists($original_uri)) {
      $original_hash = Crypt::hashBase64(file_get_contents($original_uri));
    }
    $hash = Crypt::hashBase64($body);
    // Check if file has changed (or is new).
    if ($hash !== $original_hash) {
      // Create the actual disk file from the body.
      try {
        // Make sure our destination directory exists.
        $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
        $new_uri = $this->fileSystem->createFilename($filename, $directory);
        $new_uri = $this->fileSystem->saveData($body, $new_uri, FileExists::Replace);
        if (!empty($original_uri)) {
          $this->logger->notice("Updating @original_uri to @new_uri because its hash has changed.", [
            '@original_uri' => $original_uri,
            '@new_uri' => $new_uri,
          ]);
        }
        $uri = $new_uri;
      }
      catch (\Exception $e) {
        $this->logger->notice("During migration of %url, @msg", [
          '%url' => $url,
          '@msg' => $e->getMessage(),
        ]);
      }

    }

    return $uri;
  }

}
