<?php

namespace Drupal\ib_dam\AssetFormatter;

use Drupal\ib_dam\Asset\AssetInterface;
use Drupal\ib_dam\Asset\EmbedAssetInterface;
use Drupal\ib_dam\Asset\LocalAssetInterface;

/**
 * Class AssetFormatterManager.
 *
 * Responsible to find particular formatter given asset type.
 *
 * @package Drupal\ib_dam_media\AssetFormatter
 */
class AssetFormatterManager {

  /**
   * Factory method to build correct formatter.
   *
   * @param \Drupal\ib_dam\Asset\AssetInterface $asset
   *   The asset object.
   * @param array $display_settings
   *   The display options used for formatting asset later.
   *
   * @return AssetFormatterInterface
   *   The asset formatter instance.
   */
  public static function create(AssetInterface $asset, array $display_settings = []) {
    $formatter = NULL;

    $configured_title = $display_settings['title'] ?? NULL;
    // Allow specify an empty title attribute by passing an empty string.
    $display_settings['title'] = (!empty($configured_title) || $configured_title === '')
      ? $display_settings['title']
      : $asset->getName();

    if (empty($display_settings['alt'])) {
      $display_settings['alt'] = $asset->getDescription() ?? $asset->getName();
    }

    if ($asset instanceof LocalAssetInterface) {
      $formatter = new LocalAssetFormatter(
        $asset->getType(),
        $asset->localFile()->id(),
        $display_settings
      );
      return $formatter;
    }
    elseif ($asset instanceof EmbedAssetInterface) {
      switch ($asset->getType()) {

        case 'image':
          $formatter = new EmbedImageAssetFormatter($asset->getUrl(), $asset->getType(), $display_settings);
          break;

        case 'video':
          $formatter = new EmbedVideoAssetFormatter($asset->getUrl(), $asset->getType(), $display_settings);
          break;

        case 'audio':
          $formatter = new EmbedAudioAssetFormatter($asset->getUrl(), $asset->getType(), $display_settings);
          break;

        default:
          $formatter = new EmbedLinkAssetFormatter($asset->getUrl(), $asset->getType(), $display_settings);
      }
    }
    return $formatter;
  }

  /**
   * Find match between asset type and suitable formatter.
   *
   * Returned data is statically cached.
   *
   * @param string $type
   *   The asset type to match.
   *
   * @return mixed
   *   Returns matched map or default map,
   *   the map item with following options:
   *     - 'type': string, the field type plugin id,
   *     - 'formatter': string, the field formatter plugin id.
   *     - 'extra_settings': (optional) calllable,
   *       a list of extra formatter settings.
   */
  public static function matchFieldTypeByAssetType($type) {
    $types = &drupal_static(__METHOD__, []);

    if (!empty($types)) {
      return isset($types[$type]) ? $types[$type] : $types['default'];
    }

    $types = [
      'audio'   => [
        'type' => 'file',
        'formatter' => 'file_audio',
      ],
      'video'   => [
        'type' => 'file',
        'formatter' => 'file_video',
        'extra_settings' => [
          [AssetFeatures::class, 'getViewableSettings'],
        ],
      ],
      'image'   => [
        'type' => 'image',
        'formatter' => 'image',
        'extra_settings' => [
          [AssetFeatures::class, 'getCaptionSettings'],
        ],
      ],
      'file'    => [
        'type' => 'file',
        'formatter' => 'file_default',
        'extra_settings' => [
          [AssetFeatures::class, 'getDescriptionSettings'],
        ],
      ],
      'default' => ['type' => 'file', 'formatter' => 'file_default'],
      'embed'   => ['type' => 'link', 'formatter' => 'ib_dam_embed'],
    ];
    /* @var $manager \Drupal\Core\Field\FormatterPluginManager */
    $manager = \Drupal::service('plugin.manager.field.formatter');
    $types = array_filter($types, function ($type) use ($manager) {
      return !empty($manager->getDefinition($type['formatter'], FALSE)) ? TRUE : FALSE;
    });

    return isset($types[$type])
      ? $types[$type]
      : $types['default'];
  }

}
