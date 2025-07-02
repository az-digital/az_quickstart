<?php

namespace Drupal\ib_dam\AssetFormatter;

use Drupal\Core\Render\Element\Radios;
use Drupal\Core\Template\Attribute;
use Drupal\ib_dam\Asset\AssetInterface;

/**
 * Class VideoAssetFormatter.
 *
 * @package Drupal\ib_dam\AssetFormatter
 */
class EmbedVideoAssetFormatter extends EmbedAssetFormatterBase {

  private $controls;
  private $autoplay;
  private $loop;
  private $width;
  private $height;
  private $mimetype = 'video/mp4';
  private $link_type;
  const LINK_TYPE_DIRECT = 'direct';
  const LINK_TYPE_STREAMING = 'streaming';

  /**
   * {@inheritdoc}
   */
  public function __construct($url, $type, array $display_settings) {
    parent::__construct($url, $type, $display_settings);

    $defaults = [
      'loop' => FALSE,
      'autoplay' => FALSE,
      'controls' => TRUE,
      'width' => FALSE,
      'height' => FALSE,
      'link_type' => self::LINK_TYPE_STREAMING,
    ];

    foreach ($defaults as $prop => $default) {
      $this->{$prop} = static::getVal($display_settings, $prop) ?: $default;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function format() {
    $attributes = new Attribute([]);

    $this->width = $this->width > 100
      ? $this->width
      : '100%';

    $this->height = $this->height > 100
      ? $this->height
      : FALSE;

    $attributes->setAttribute('width', $this->width);

    if (is_numeric($this->height)) {
      $attributes->setAttribute('height', $this->height);
    }
    if ($this->controls) {
      $attributes->setAttribute('controls', '');
    }
    if ($this->autoplay) {
      $attributes->setAttribute('autoplay', '');
    }
    if ($this->loop) {
      $attributes->setAttribute('loop', '');
    }
    $attributes->setAttribute('frameBorder', '0');

    $is_direct = $this->link_type === self::LINK_TYPE_DIRECT;

    $theme = [
      '#title' => $this->title,
      '#theme' => 'ib_dam_embed_playable_resource',
      '#resource_type' => 'iframe',
      '#url' => $this->url,
      '#attributes' => $attributes,
    ];

    if ($is_direct) {
      $theme += ['#mimetype' => $this->mimetype];
      $theme['#url'] = str_replace('/streaming/', '/mp4/', $theme['#url']);
      $theme['#resource_type'] = 'video';
    }

    return $theme;
  }

  public function setMimeType(string $mimetype) {
    $this->mimetype = $mimetype;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(AssetInterface $asset = NULL): array {
    $settings = [];
    $settings += AssetFeatures::getPlayableSettings();
    $settings += AssetFeatures::getViewableSettings();

    foreach (array_keys(AssetFeatures::getPlayableSettings()) as $setting) {
      $settings[$setting]['#states'] = [
        'invisible' => [
          ':input[name*="link_type"]' => ['value' => self::LINK_TYPE_STREAMING],
        ],
      ];
    }

    $link_type = ['link_type' => [
        '#title' => $this->t('Link Type'),
        '#type' => 'radios',
        '#options' => [
          self::LINK_TYPE_STREAMING => $this->t('Streaming'),
          self::LINK_TYPE_DIRECT => $this->t('Direct'),
        ],
        '#process' => [[Radios::class, 'processRadios'], [$this, 'processLinkTypeOptions']],
        '#default_value' => self::LINK_TYPE_STREAMING,
        '#attached' => [
          'library' => [
            'ib_dam/link_type_checker',
          ],
        ],
      ],
    ];
    return $link_type + $settings + parent::settingsForm($asset);
  }

  /**
   * Provide link_type option's description.
   */
  public function processLinkTypeOptions(array $element): array {
    $element[self::LINK_TYPE_DIRECT]['#description'] = $this->t('Configure playable options like: autoplay, loop, show controls. Uses default browser video player.');
    $element[self::LINK_TYPE_STREAMING]['#description'] = $this->t('Show video in an embedded video player via iframe.');

    return $element;
  }

}
