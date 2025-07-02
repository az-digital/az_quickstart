<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\blazy\BlazyDefault;
use Drupal\blazy\Field\BlazyEntityVanillaBase;

/**
 * Provides blazy grid for entity references.
 *
 * @FieldFormatter(
 *   id = "blazy_entity",
 *   label = @Translation("Blazy Grid"),
 *   field_types = {
 *     "entity_reference",
 *     "entity_reference_revisions",
 *   }
 * )
 */
class BlazyEntityFormatter extends BlazyEntityVanillaBase {

  /**
   * {@inheritdoc}
   */
  protected static $namespace = 'blazy';

  /**
   * {@inheritdoc}
   */
  protected static $itemId = 'content';

  /**
   * {@inheritdoc}
   */
  protected static $itemPrefix = 'blazy';

  /**
   * {@inheritdoc}
   */
  protected static $captionId = 'captions';

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return BlazyDefault::gridEntitySettings() + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getFieldStorageDefinition()->isMultiple();
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginScopes(): array {
    return [
      'grid_form'     => TRUE,
      'grid_required' => TRUE,
      'style'         => TRUE,
      'vanilla'       => FALSE,
    ] + parent::getPluginScopes();
  }

  /**
   * {@inheritdoc}
   */
  protected function pluginSettings(&$blazies, array &$settings): void {
    parent::pluginSettings($blazies, $settings);

    $blazies->set('is.vanilla', TRUE);
    $settings['vanilla'] = TRUE;
  }

}
