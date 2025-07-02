<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\blazy\BlazyDefault;

/**
 * Plugin implementation of the 'Blazy File' to get image/ SVG from files.
 *
 * This was previously for deprecated VEF, since 2.17 re-purposed for SVG, WIP!
 *
 * @FieldFormatter(
 *   id = "blazy_file",
 *   label = @Translation("Blazy File/SVG"),
 *   field_types = {
 *     "entity_reference",
 *     "file",
 *     "image",
 *     "svg_image_field",
 *   }
 * )
 *
 * @todo remove `image` at 3.x, unless dedicated for SVG (forms and displays).
 */
class BlazyFileFormatter extends BlazyFormatterBlazy {

  /**
   * {@inheritdoc}
   */
  protected static $fieldType = 'entity';

  /**
   * {@inheritdoc}
   */
  protected static $byDelta = TRUE;

  /**
   * {@inheritdoc}
   */
  protected static $useOembed = TRUE;

  /**
   * {@inheritdoc}
   */
  protected static $useSvg = TRUE;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return BlazyDefault::svgSettings() + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getFieldStorageDefinition()->getSetting('target_type') === 'file';
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginScopes(): array {
    return $this->getEntityScopes() + parent::getPluginScopes();
  }

}
