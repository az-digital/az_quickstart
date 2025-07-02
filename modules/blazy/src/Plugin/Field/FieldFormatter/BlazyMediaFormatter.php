<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

/**
 * Plugin for blazy media formatter.
 *
 * @FieldFormatter(
 *   id = "blazy_media",
 *   label = @Translation("Blazy Media"),
 *   field_types = {
 *     "entity_reference",
 *   }
 * )
 *
 * @see \Drupal\blazy\Plugin\Field\FieldFormatter\BlazyMediaFormatterBase
 * @see \Drupal\media\Plugin\Field\FieldFormatter\MediaThumbnailFormatter
 */
class BlazyMediaFormatter extends BlazyMediaFormatterBase {

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
   *
   * @todo make it caption similar to sub-modules for easy 3.x migrations.
   */
  protected static $captionId = 'captions';

  /**
   * {@inheritdoc}
   */
  protected static $byDelta = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function getPluginScopes(): array {
    $multiple = $this->isMultiple();

    return [
      'grid_form'       => $multiple,
      'layouts'         => [],
      'style'           => $multiple,
      'vanilla'         => FALSE,
    ] + parent::getPluginScopes();
  }

}
