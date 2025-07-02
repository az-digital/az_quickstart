<?php

declare(strict_types=1);

namespace Drupal\sophron;

use Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser;

/**
 * Extends the class that Drupal core uses to guess the MIME type of a file.
 *
 * This class is used only to access the protected properities in the parent
 * class, so to make it possible to compare Sophron's supported MIME types with
 * Drupal core ones.
 */
class CoreExtensionMimeTypeGuesserExtended extends ExtensionMimeTypeGuesser {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new CoreExtensionMimeTypeGuesserExtended.
   */
  public function __construct() {
    $this->moduleHandler = \Drupal::service('module_handler');
  }

  /**
   * Returns a list of MIME types supported by Drupal's core guesser.
   *
   * @return string[]
   *   A list of MIME types.
   */
  public function listTypes(): array {
    return $this->getMapping()['mimetypes'];
  }

  /**
   * Returns a list of file extensions supported by Drupal's core guesser.
   *
   * @return string[]
   *   A list of file extensions.
   */
  public function listExtensions(): array {
    return array_keys($this->getMapping()['extensions']);
  }

  /**
   * Ensures Drupal's core MIME type mapping is altered by modules.
   */
  protected function getMapping(): array {
    if ($this->mapping === NULL) {
      $mapping = $this->defaultMapping;
      // Allow modules to alter the default mapping.
      $this->moduleHandler->alter('file_mimetype_mapping', $mapping);
      $this->mapping = $mapping;
    }
    return $this->mapping;
  }

}
