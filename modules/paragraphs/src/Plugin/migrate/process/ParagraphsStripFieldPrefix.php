<?php

namespace Drupal\paragraphs\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateException;

use Drupal\paragraphs\Plugin\migrate\field\FieldCollection;

/**
 * Remove 'field_' from the start of a string.
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "paragraphs_strip_field_prefix"
 * )
 */
class ParagraphsStripFieldPrefix extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_string($value)) {
      throw new MigrateException('The input value must be a string.');
    }

    if (mb_substr($value, 0, FieldCollection::FIELD_COLLECTION_PREFIX_LENGTH) === 'field_') {
      return mb_substr($value, FieldCollection::FIELD_COLLECTION_PREFIX_LENGTH);
    }
    else {
      return $value;
    }
  }

}
