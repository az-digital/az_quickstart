<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Plugin\migrate\process;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Copy a file from a blob into a file.
 *
 * The source value is an indexed array of two values:
 * - The destination URI, e.g. 'public://example.txt'.
 * - The binary blob data.
 *
 * Available configuration keys:
 * - reuse: (optional) Indicates whether to overwrite existing files. If TRUE,
 *   then existing files won't be replaced, and previously copied files will be
 *   reused. Defaults to FALSE.
 *
 * Examples:
 * @code
 * uri:
 *   plugin: file_blob
 *   source:
 *     - 'public://example.txt'
 *     - blob
 * @endcode
 * Above, a basic configuration.
 *
 * @code
 * source:
 *   constants:
 *     destination: public://images
 * process:
 *   destination_blob:
 *     plugin: callback
 *     callable: base64_decode
 *     source:
 *       - blob
 *   destination_basename:
 *     plugin: callback
 *     callable: basename
 *     source: file_name
 *   destination_path:
 *     plugin: concat
 *     source:
 *       - constants/destination
 *       - @destination_basename
 *   uri:
 *     plugin: file_blob
 *     source:
 *       - @destination_path
 *       - @destination_blob
 * @endcode
 *
 * In the example above, it is necessary to manipulate the values before they
 * are processed by this plugin. This is because this plugin takes a binary blob
 * and saves it as a file. In many cases, as in this example, the data is base64
 * encoded and should be decoded first. In destination_blob, the incoming data
 * is decoded from base64 to binary. The destination_path element is
 * concatenating the base filename with the destination directory set in the
 * constants to create the final path. The resulting values are then referenced
 * as the source of the file_blob plugin.
 *
 * @MigrateProcessPlugin(
 *   id = "file_blob"
 * )
 */
class FileBlob extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  protected FileSystemInterface $fileSystem;

  /**
   * Constructs a file_blob process plugin.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FileSystemInterface $file_system) {
    $configuration += [
      'reuse' => FALSE,
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // If we're stubbing a file entity, return a URI of NULL so it will get
    // stubbed by the general process.
    if ($row->isStub()) {
      return NULL;
    }
    [$destination, $blob] = $value;

    // Determine if we're going to overwrite existing files or not touch them.
    $replace = $this->getOverwriteMode();

    // Create the directory or modify permissions if necessary
    $dir = $this->getDirectory($destination);
    $success = $this->fileSystem->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    if (!$success) {
      throw new MigrateSkipProcessException("Could not create directory '$dir'");
    }

    // Attempt to save the file
    if (!$this->putFile($destination, $blob, $replace)) {
      throw new MigrateSkipProcessException("Blob data could not be copied to $destination.");
    }

    return $destination;
  }

  /**
   * Try to save the file.
   *
   * @param string $destination
   *   The destination path or URI.
   * @param string $blob
   *   The base64 encoded file contents.
   * @param int $replace
   *   (optional) either FileSystemInterface::EXISTS_REPLACE; (default) or
   *   FileSystemInterface::EXISTS_ERROR, depending on the configuration.
   *
   * @return bool|string
   *   File path on success, FALSE on failure.
   */
  protected function putFile(string $destination, string $blob, int $replace = FileSystemInterface::EXISTS_REPLACE) {
    $path = $this->fileSystem->getDestinationFilename($destination, $replace);
    if ($path) {
      if (file_put_contents($path, $blob)) {
        return $path;
      }
      else {
        return FALSE;
      }
    }

    // File was already copied.
    return $destination;
  }

  /**
   * Determines how to handle file conflicts.
   *
   *   Either FileSystemInterface::EXISTS_REPLACE; (default) or
   *   FileSystemInterface::EXISTS_ERROR, depending on the configuration.
   */
  protected function getOverwriteMode(): int {
    if (isset($this->configuration['reuse']) && !empty($this->configuration['reuse'])) {
      return FileSystemInterface::EXISTS_ERROR;
    }
    return FileSystemInterface::EXISTS_REPLACE;
  }

  /**
   * Returns the directory component of a URI or path.
   *
   * For URIs like public://foo.txt, the full physical path of public://
   * will be returned, since a scheme by itself will trip up certain file
   * API functions (such as file_prepare_directory()).
   *
   * @param string $uri
   *   The URI or path.
   *
   * @return string|false
   *   The directory component of the path or URI, or FALSE if it could not
   *   be determined.
   */
  protected function getDirectory(string $uri) {
    $dir = $this->fileSystem->dirname($uri);
    if (substr($dir, -3) === '://') {
      return $this->fileSystem->realpath($dir);
    }
    return $dir;
  }

}
