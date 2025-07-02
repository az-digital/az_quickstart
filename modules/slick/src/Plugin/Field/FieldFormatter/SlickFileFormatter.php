<?php

namespace Drupal\slick\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\slick\SlickDefault;

/**
 * Plugin implementation of the 'Slick File' to get image/ SVG from files.
 *
 * This was previously for deprecated VEF, since 2.10 re-purposed for SVG, WIP!
 *
 * @FieldFormatter(
 *   id = "slick_file",
 *   label = @Translation("Slick File/SVG"),
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
class SlickFileFormatter extends SlickFileFormatterBase {

  /**
   * {@inheritdoc}
   */
  protected static $fieldType = 'entity';

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
    return SlickDefault::svgSettings() + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function buildSettings() {
    return ['blazy' => TRUE] + parent::buildSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginScopes(): array {
    // @todo use $this->getEntityScopes() post blazy:2.17.
    return [
      'fieldable_form'   => TRUE,
      'multimedia'       => TRUE,
      'no_loading'       => TRUE,
      'no_preload'       => TRUE,
      'responsive_image' => FALSE,
    ] + parent::getPluginScopes();
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $storage = $field_definition->getFieldStorageDefinition();
    return $storage->isMultiple() && $storage->getSetting('target_type') === 'file';
  }

}
