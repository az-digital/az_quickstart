<?php

declare(strict_types=1);

namespace Drupal\file_mdm;

/**
 * Exception thrown by file_mdm and plugins on failures.
 */
class FileMetadataException extends \Exception {

  public function __construct(string $message, string $plugin_id = NULL, string $method = NULL, \Exception $previous = NULL) {
    $msg = $message;
    $msg .= $plugin_id ? " (plugin: {$plugin_id})" : "";
    $msg .= $method ? " (method: {$method})" : "";
    parent::__construct($msg, 0, $previous);
  }

}
