<?php

namespace Drupal\ib_dam\AssetFormatter;

use Drupal\Component\Utility\Xss;
use Drupal\ib_dam\Asset\AssetInterface;

/**
 * Class ImageAssetFormatter.
 *
 * @package Drupal\ib_dam\AssetFormatter
 */
class EmbedImageAssetFormatter extends EmbedAssetFormatterBase {

  private $alt;
  private $width;
  private $height;

  /**
   * {@inheritdoc}
   */
  public function __construct($url, $type, array $display_settings = []) {
    parent::__construct($url, $type, $display_settings);

    foreach (['alt', 'width', 'height'] as $prop) {
      $this->{$prop} = static::getVal($display_settings, $prop) ?: '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function format() {
    if (empty($this->url)) {
      return [];
    }

    $element = [
      '#theme'  => 'image',
      '#uri'    => $this->url,
      '#alt'    => $this->alt,
      '#title'  => $this->title,
    ];

    $element['#width'] = !empty($this->width)
      ? $this->width
      : '100%';

    if (!empty($this->height)) {
      $element['#height'] = $this->height;
    }

    foreach (['alt', 'title', 'width', 'height'] as $unsafe_attr) {
      if (empty($element["#$unsafe_attr"])) {
        continue;
      }
      $attr_value =& $element["#$unsafe_attr"];
      $attr_value = Xss::filter($attr_value);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(AssetInterface $asset = NULL) {
    $settings = [];
    $settings += AssetFeatures::getCaptionSettings([
      'alt'   => $this->alt ?? $this->title,
      'title' => $this->title,
    ]);
    $settings += AssetFeatures::getViewableSettings();
    return $settings + parent::settingsForm($asset);
  }

}
