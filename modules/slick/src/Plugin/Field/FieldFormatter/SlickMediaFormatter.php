<?php

namespace Drupal\slick\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'slick media' formatter.
 *
 * @FieldFormatter(
 *   id = "slick_media",
 *   label = @Translation("Slick Media"),
 *   description = @Translation("Display the referenced entities as a Slick carousel."),
 *   field_types = {
 *     "entity_reference",
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class SlickMediaFormatter extends SlickEntityReferenceFormatterBase {

  /**
   * Returns the blazy manager.
   */
  public function blazyManager() {
    return $this->formatter;
  }

  /**
   * Builds the settings.
   */
  public function buildSettings() {
    return ['blazy' => TRUE] + parent::buildSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $storage = $field_definition->getFieldStorageDefinition();
    return $storage->isMultiple() && $storage->getSetting('target_type') === 'media';
  }

}
