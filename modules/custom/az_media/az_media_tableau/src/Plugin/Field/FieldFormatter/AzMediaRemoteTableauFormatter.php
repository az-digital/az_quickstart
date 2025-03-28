<?php

namespace Drupal\az_media_tableau\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\media_remote\Plugin\Field\FieldFormatter\MediaRemoteFormatterBase;

/**
 * Plugin implementation of the 'az_media_remote_tableau' formatter.
 */
#[FieldFormatter(
  id: 'az_media_remote_tableau',
  label: new TranslatableMarkup('Remote Media - Tableau Visualization'),
  field_types: [
    'string',
  ],
)]
class AzMediaRemoteTableauFormatter extends MediaRemoteFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function getUrlRegexPattern() {
    return '/^https:\/\/bi\.arizona\.edu\/t\/UA\/views\/([a-zA-Z0-9_\-]+)\/([a-zA-Z0-9_\-]+)\?/';
  }

  /**
   * {@inheritdoc}
   */
  public static function getValidUrlExampleStrings(): array {
    return [
      'https://bi.arizona.edu/t/UA/views/[your-viz-name]/[your-dashboard-name]?embed=y&:tabs=n&:navSrc=Parse&:showVizHome=no',
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
      return t('Tableau Visualization from @url', [
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
      $fieldValue = $item->getValue();

      $elements[$delta] = [
        '#theme' => 'az_media_tableau',
        '#url' => $fieldValue['value'],
      ];
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return parent::settingsForm($form, $form_state) + [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    return $summary;
  }

}
