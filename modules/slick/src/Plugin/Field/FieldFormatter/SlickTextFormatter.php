<?php

namespace Drupal\slick\Plugin\Field\FieldFormatter;

use Drupal\blazy\Plugin\Field\FieldFormatter\BlazyTextFormatter;
use Drupal\slick\SlickDefault;

/**
 * Plugin implementation of the 'Slick Text' formatter.
 *
 * @FieldFormatter(
 *   id = "slick_text",
 *   label = @Translation("Slick Text"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *   },
 *   quickedit = {"editor" = "disabled"}
 * )
 */
class SlickTextFormatter extends BlazyTextFormatter {

  use SlickFormatterTrait {
    buildSettings as traitBuildSettings;
  }

  /**
   * {@inheritdoc}
   */
  protected static $namespace = 'slick';

  /**
   * {@inheritdoc}
   */
  protected static $itemId = 'slide';

  /**
   * {@inheritdoc}
   */
  protected static $itemPrefix = 'slide';

  /**
   * {@inheritdoc}
   */
  protected static $fieldType = 'text';

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return SlickDefault::baseSettings() + SlickDefault::gridSettings();
  }

  /**
   * Builds the settings.
   */
  public function buildSettings() {
    return ['vanilla' => TRUE] + $this->traitBuildSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginScopes(): array {
    return [
      'no_thumb_effects' => TRUE,
    ] + parent::getPluginScopes();
  }

}
