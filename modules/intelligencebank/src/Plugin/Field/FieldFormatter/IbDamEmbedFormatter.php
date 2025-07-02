<?php

namespace Drupal\ib_dam\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ib_dam\Asset\Asset;
use Drupal\ib_dam\AssetFormatter\AssetFormatterManager;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;

/**
 * Plugin implementation of the 'link' formatter.
 *
 * @FieldFormatter(
 *   id = "ib_dam_embed",
 *   label = @Translation("IntelligenceBank Embed Formatter"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class IbDamEmbedFormatter extends LinkFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'no_link' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['url_only']['#weight'] = 1;
    $elements['url_only']['#access'] = TRUE;

    $elements['url_plain']['#weight'] = 2;
    $elements['url_plain']['#access'] = TRUE;

    foreach (['url_only', 'url_plain', 'target', 'rel'] as $id) {
      $elements[$id]['#states'] = [
        'visible' => [
          ':input[name*="no_link"]' => ['checked' => FALSE],
        ],
      ];
    }

    $elements['no_link'] = [
      '#type' => 'checkbox',
      '#weight' => 2,
      '#title' => $this->t('Do not wrap embed content into link'),
      '#default_value' => $this->getSetting('no_link'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary  = parent::settingsSummary();
    $settings = $this->getSettings();

    if (!empty($settings['no_link'])) {
      $summary   = [];
      $summary[] = $this->t('Do not wrap embed content into link');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $settings = $this->getSettings();
    $element  = [];

    if (!empty($settings['url_only'])) {
      return parent::viewElements($items, $langcode);
    }

    if (!empty($settings['no_link'])) {
      $original_items = $items;
      $element_type   = 'object';
    }
    else {
      $original_items = parent::viewElements($items, $langcode);
      $element_type   = 'array';
    }

    foreach ($original_items as $delta => &$item) {
      /* @var $url \Drupal\Core\Url */
      $url = $element_type === 'array'
        ? $item['#url']
        : $item->getUrl();

      $options = $url->getOptions()['attributes']['ib_dam'] ?? [];
      $title = $element_type === 'array'
        ? $item['#title']
        : $item->title;
      $display_settings = $options['extra'] ?? [];

      if (empty($display_settings['alt'])) {
        $display_settings['alt'] = $title;
      }

      if (empty($display_settings['width'])) {
        $display_settings['width'] = 0;
      }

      if (empty($display_settings['height'])) {
        $display_settings['height'] = 0;
      }

      $asset = Asset::createFromValues([
        'type' => $options['asset_type'],
        'name' => $title,
        'remote_url' => $url->getUri(),
      ]);

      $orig_attrs = $url->getOptions()['attributes'];
      if ($element_type === 'array' && !empty($orig_attrs['ib_dam'])) {
        unset($orig_attrs['ib_dam']);
        unset($item['#options']['attributes']['ib_dam']);
        $url->setOption('attributes', $orig_attrs);
        $item['#url'] = $url;
      }

      $formatter = AssetFormatterManager::create($asset, $display_settings);

      $output = $formatter->format();
      // Insert asset into generated link.
      if ($element_type === 'array') {
        !$output ?: $item['#title'] = $output;
      }
      else {
        $element[$delta] = $output;
      }

    }
    return !empty($element)
      ? $element
      : $original_items;
  }

  /**
   * Return field plugin settings.
   *
   * Force setting of default value for url_only.
   *
   * @return array
   *   The settings array.
   */
  public function getSettings() {
    $settings = parent::getSettings();
    if ($settings['no_link']) {
      $settings['url_only'] = FALSE;
    }
    return $settings;
  }

}
