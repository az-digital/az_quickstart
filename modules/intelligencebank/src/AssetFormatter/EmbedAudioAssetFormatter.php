<?php

namespace Drupal\ib_dam\AssetFormatter;

use Drupal\Core\Template\Attribute;
use Drupal\ib_dam\Asset\AssetInterface;

/**
 * Class AudioAssetFormatter.
 *
 * @package Drupal\ib_dam\AssetFormatter
 */
class EmbedAudioAssetFormatter extends EmbedAssetFormatterBase {

  private $controls;
  private $autoplay;
  private $loop;
  private $mimetype = 'audio/mp3';

  /**
   * {@inheritdoc}
   */
  public function __construct($url, $type, array $display_settings = []) {
    parent::__construct($url, $type, $display_settings);

    $defaults = ['loop' => FALSE, 'autoplay' => FALSE, 'controls' => TRUE];

    foreach ($defaults as $prop => $default) {
      $this->{$prop} = static::getVal($display_settings, $prop) ?: $default;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function format() {
    $attributes = new Attribute([]);

    if ($this->controls) {
      $attributes->setAttribute('controls', '');
    }
    if ($this->autoplay) {
      $attributes->setAttribute('autoplay', '');
    }
    if ($this->loop) {
      $attributes->setAttribute('loop', '');
    }

    return [
      '#theme' => 'ib_dam_embed_playable_resource',
      '#resource_type' => 'audio',
      '#attributes' => $attributes,
      '#title' => $this->title,
      '#url' => $this->url,
      '#mimetype' => $this->mimetype,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(AssetInterface $asset = NULL) {
    return AssetFeatures::getPlayableSettings() + parent::settingsForm($asset);
  }

}
