<?php

namespace Drupal\az_migration_remote_media\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\ProcessPluginBase;
use Devanych\Mime\MimeTypes;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\Event\FileUploadSanitizeNameEvent;

/**
 * Provides uri to created file in the file system after a remote download.
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 */
#[MigrateProcess('az_migration_remote_media')]
class MigrationRemoteMedia extends ProcessPluginBase implements ContainerFactoryPluginInterface {

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
    $instance->httpClient = $container->get('http_client');
    $instance->logger = $container->get('logger.factory')->get('az_migration_remote_media');
    $instance->pluginManagerMigration = $container->get('plugin.manager.migration');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $url = $value;
    if (empty($url)) {
      return NULL;
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
      return NULL;
    }
    // Get headers relevant to file.
    $type = $response->getHeader('Content-Type') ?? [];
    $type = array_pop($type);
    if (empty($type)) {
      $this->logger->notice("No Content-Type provided by %url", [
        '%url' => $url,
      ]);
      return NULL;
    }
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
    // @todo make configurable.
    $directory = 'public://';

    // Sanitize our filename. Give other modules a change to weigh in.
    $event = new FileUploadSanitizeNameEvent($filename, $extension);
    $this->eventDispatcher->dispatch($event);
    $filename = $event->getFilename();
    $this->logger->debug("Sanitized to %filename", [
      '%filename' => $filename,
    ]);

    // @todo check hash to determine whether to return this file or original.
    // Create the actual disk file.
    $uri = $this->fileSystem->createFilename($filename, $directory);
    $uri = $this->fileSystem->saveData($body, $uri, FileExists::Replace);

    return $uri;

  }

}
