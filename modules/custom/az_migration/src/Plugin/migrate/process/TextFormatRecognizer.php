<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process Plugin to recognize text formats and return a given response.
 *
 * @MigrateProcessPlugin(
 *   id = "text_format_recognizer"
 * )
 */
class TextFormatRecognizer extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Likely dealing with a text field.
    if (!empty($value['value'])) {
      $value = $value['value'];
    }

    if (!empty($value) && !empty($this->configuration['format']) &&
      !empty($this->configuration['passed']) && !empty($this->configuration['failed'])) {
      $format = $this->configuration['format'];

      // Render as full html first.
      $value = trim(_filter_autop(check_markup($value, 'full_html')));

      // Render the text according to the format.
      // Attempt to put autoparagraphs back in after the fact, since they
      // Are likely to exist in the source whether the user means to use
      // Them or not.
      $markup = trim(_filter_autop(check_markup($value, $format)));

      // Length change implies content filtering.
      if (mb_strlen($markup) >= mb_strlen($value)) {
        return $this->configuration['passed'];
      }
      else {
        return $this->configuration['failed'];
      }

    }
  }

}
