<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Plugin\migrate\process;

use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;

/**
 * Base class for process plugins that work with \DOMDocument objects.
 *
 * Use Dom::import() to convert a string to a \DOMDocument object, then plugins
 * derived from this class to manipulate the object, then Dom::export() to
 * convert back to a string.
 */
abstract class DomProcessBase extends ProcessPluginBase {

  protected ?\DOMDocument $document = NULL;
  protected ?\DOMXPath $xpath = NULL;

  /**
   * Initialize the class properties.
   *
   * @param mixed $value
   *   Process plugin value.
   * @param string $destination_property
   *   The name of the destination being processed. Used to generate an error
   *   message.
   *
   * @throws \Drupal\migrate\MigrateSkipRowException
   *   If $value is not a \DOMDocument object.
   */
  protected function init($value, string $destination_property) {
    if (!($value instanceof \DOMDocument)) {
      $message = sprintf(
        'The %s plugin in the %s process pipeline requires a \DOMDocument object. You can use the dom plugin to convert a string to \DOMDocument.',
        $this->getPluginId(),
        $destination_property
      );
      throw new MigrateSkipRowException($message);
    }
    $this->document = $value;
    $this->xpath = new \DOMXPath($this->document);
  }

}
