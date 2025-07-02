<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

/**
 * Plugin for the Blazy image formatter.
 *
 * @FieldFormatter(
 *   id = "blazy",
 *   label = @Translation("Blazy Image"),
 *   field_types = {"image"}
 * )
 */
class BlazyImageFormatter extends BlazyFormatterBlazy {

  /**
   * {@inheritdoc}
   */
  protected static $byDelta = TRUE;

}
