<?php

namespace Drupal\ib_dam\AssetFormatter;

use Drupal\Core\Url;
use Drupal\ib_dam\Asset\AssetInterface;

/**
 * Class LinkAssetFormatter.
 *
 * @package Drupal\ib_dam\AssetFormatter
 */
class EmbedLinkAssetFormatter extends EmbedAssetFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function format() {
    return [
      '#type' => 'link',
      '#url' => Url::fromUri($this->url),
      '#title' => $this->title,
      '#options' => [
        'external' => TRUE,
        'attributes' => [
          'download' => TRUE,
          'rel' => 'nofollow',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(AssetInterface $asset = NULL) {
    $settings = [
      'url_only' => [
        '#title' => $this->t('Show plain URL'),
        '#type' => 'checkbox',
        '#default_value' => FALSE,
      ],
      'title' => [
        '#type' => 'textfield',
        '#title' => $this->t('Link text'),
        '#maxlenght' => 255,
        '#default_value' => $asset->getName(),
        '#states' => [
          'invisible' => [
            ':input[name*="url_only"]' => ['checked' => TRUE],
          ],
        ],
      ],
    ];
    return $settings + parent::settingsForm($asset);
  }

}
