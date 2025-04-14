<?php

namespace Drupal\az_media_trellis\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\media_remote\Plugin\Field\FieldFormatter\MediaRemoteFormatterBase;

/**
 * Plugin implementation of the 'az_media_remote_trellis' formatter.
 *
 */
#[FieldFormatter(
  id: 'az_media_remote_trellis',
  label: new TranslatableMarkup('Remote Media - Trellis Form'),
  field_types: [
    'string',
  ],
)]
class AzMediaRemoteTrellisFormatter extends MediaRemoteFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function getUrlRegexPattern() {
    return '/^https:\/\/forms-a\.trellis\.arizona\.edu\/([0-9]+)\?tfa_4=(.*)/';
  }

  /**
   * {@inheritdoc}
   */
  public static function getValidUrlExampleStrings(): array {
    return [
      'https://forms-a.trellis.arizona.edu/185?tfa_4=7018N00000072edQAA',
      'https://forms-a.trellis.arizona.edu/185?tfa_4=7018N00000071eDQAQ',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function deriveMediaDefaultNameFromUrl($url) {
    $matches = [];
    $pattern = static::getUrlRegexPattern();
    preg_match_all($pattern, $url, $matches);
    if (!empty($matches[1][0])) {
      return t('Trellis Form at @url', [
        '@url' => $url,
      ]);
    }
    return parent::deriveMediaDefaultNameFromUrl($url);
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      /** @var \Drupal\Core\Field\FieldItemInterface $item */
      if ($item->isEmpty()) {
        continue;
      }
      $fieldValue = $item->getValue()['value'];
      \Drupal::logger('az_media_trellis')->info('fieldValue: ' . $fieldValue);


      $url = $fieldValue;
      // Parse URL to remove query string
      $parsedUrl = parse_url($url);
      $path = $parsedUrl['path']; // e.g., /185

      // Insert 'publish' before '185'
      $pathParts = explode('/', trim($path, '/')); // ['185']
      $pathParts = array_merge(['publish'], $pathParts); // ['publish', '185']

      // Reconstruct the new URL
      $newPath = '/' . implode('/', $pathParts);
      $newUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $newPath;


      // Check if the current route is an editing context.
      $route_name = \Drupal::routeMatch()->getRouteName();
      $is_editing_context = in_array($route_name, [
      // Node edit form.
        'entity.node.edit_form',
      // Node add form.
        'entity.node.add_form',
      // Media library.
        'media_library.ui',
      // When editing the media inline?
        'media.filter.preview',
      ]);

      $elements[$delta] = [
        '#theme' => 'az_media_trellis',
        '#url' => $newUrl,
        // '#width' => $this->getSetting('width') ?? 800,
        // '#height' => $this->getSetting('height') ?? 600,
        '#editing' => $is_editing_context,
      ];
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    // return [
    //   'width' => 960,
    //   'height' => 600,
    // ] + parent::defaultSettings();
    return parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return parent::settingsForm($form, $form_state) + [
      'url' => [
        '#type' => 'string',
        '#title' => $this->t('URL'),
        '#size' => 255,
        '#maxlength' => 255,
        '#description' => $this->t('The URL of the Trellis form.'),
      ],
      // 'width' => [
      //   '#type' => 'number',
      //   '#title' => $this->t('Width'),
      //   '#default_value' => $this->getSetting('width'),
      //   '#size' => 5,
      //   '#maxlength' => 5,
      //   '#field_suffix' => $this->t('pixels'),
      //   '#min' => 50,
      // ],
      // 'height' => [
      //   '#type' => 'number',
      //   '#title' => $this->t('Height'),
      //   '#default_value' => $this->getSetting('height'),
      //   '#size' => 5,
      //   '#maxlength' => 5,
      //   '#field_suffix' => $this->t('pixels'),
      //   '#min' => 50,
      // ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Iframe size: %width x %height pixels', [
      // '%width' => $this->getSetting('width'),
      // '%height' => $this->getSetting('height'),
    ]);
    return $summary;
  }

}
