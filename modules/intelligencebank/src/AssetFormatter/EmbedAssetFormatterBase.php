<?php

namespace Drupal\ib_dam\AssetFormatter;

use Drupal\ib_dam\Asset\AssetInterface;
use Drupal\ib_dam\Asset\EmbedAssetInterface;

/**
 * Class EmbedAssetFormatterBase.
 *
 * Base class for embed formatters.
 *
 * @package Drupal\ib_dam\AssetFormatter
 */
abstract class EmbedAssetFormatterBase extends AssetFormatterBase {

  protected $url;
  protected $title;

  /**
   * EmbedAssetFormatterBase constructor.
   *
   * @param string $url
   *   The asset remote url.
   * @param string $type
   *   The asset type.
   * @param array $display_settings
   *   List of display settings used as formatter options.
   */
  public function __construct($url, $type, array $display_settings) {
    parent::__construct($type, $display_settings);
    $this->title = static::getVal($display_settings, 'title') ?: '';
    $this->url   = html_entity_decode($url);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(AssetInterface $asset = NULL) {
    if (!$asset instanceof EmbedAssetInterface) {
      return [];
    }
    // the thing is that we using remote_url in embed asset in CKEditor integration,
    // to store embeddable link but also we are using this url to customize image url params.
    $url = 'https://help.intelligencebank.com/hc/en-us/search?utf8=✓&category=200991266&query=Manual+Transformations+of+Image';
    $link_text = 'Manual Transformations of Image';
    return [
      'remote_url' => [
        '#type'          => 'textfield',
        '#title'         => $this->t('Remote URL'),
        '#description'   => $this->t('Here you can customize your remote URL.<br>See <a href=":url" target="_blank">@link_text</a> to get more details', [':url' => $url, '@link_text' => $link_text]),
        '#maxlength'     => 255,
        '#size'          => 120,
        '#default_value' => $asset->getUrl() ?? '',
      ],
    ];
  }

}
