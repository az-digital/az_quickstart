<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\blazy\BlazyDefault;
use Drupal\blazy\Field\BlazyEntitySvgBase;

/**
 * Base class for blazy-related media ER formatters.
 *
 * @see \Drupal\blazy\Plugin\Field\FieldFormatter\BlazyMediaFormatter
 * @see \Drupal\gridstack\Plugin\Field\FieldFormatter\GridStackMediaFormatter
 */
abstract class BlazyMediaFormatterBase extends BlazyEntitySvgBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return BlazyDefault::extendedSettings()
      + BlazyDefault::gridSettings()
      + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getFieldStorageDefinition()->getSetting('target_type') == 'media';
  }

}
