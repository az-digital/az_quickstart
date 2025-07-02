<?php

namespace Drupal\ib_dam\AssetFormatter;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class AssetFormatterBase.
 *
 * @package Drupal\ib_dam\AssetFormatter
 */
abstract class AssetFormatterBase implements AssetFormatterInterface {

  use StringTranslationTrait;

  protected $settings;
  protected $assetType;

  /**
   * AssetFormatterBase constructor.
   *
   * @param string $type
   *   The asset type.
   * @param array $display_settings
   *   List of display settings used as formatter options.
   */
  public function __construct($type, array $display_settings) {
    $this->settings = $display_settings;
    $this->assetType = $type;
  }

  /**
   * Isset wrapper.
   */
  protected static function getVal($container, $key) {
    return !isset($container[$key]) ? NULL : $container[$key];
  }

}
