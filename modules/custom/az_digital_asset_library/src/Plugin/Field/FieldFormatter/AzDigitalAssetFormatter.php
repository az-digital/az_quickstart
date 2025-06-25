<?php

namespace Drupal\az_digital_asset_library\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ib_dam\Asset\Asset;
use Drupal\ib_dam\AssetFormatter\AssetFormatterManager;
use Drupal\Component\Utility\UrlHelper;

/**
 * Plugin implementation of the 'az_digital_asset' formatter.
 *
 * @FieldFormatter(
 *   id = "az_digital_asset_library_remote",
 *   label = @Translation("AZ Digital Asset (Transformed Image)"),
 *   field_types = {
 *     "string",
 *     "link"
 *   }
 * )
 */
class AzDigitalAssetFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'width' => 100,
      'height' => 100,
      'ignore_aspect_ratio' => TRUE,
      'crop_width' => '',
      'crop_height' => '',
      'crop_gravity' => '',
      'dpi' => '',
      'alt' => '',
      'url_only' => FALSE,
      'no_link' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Width (px)'),
      '#default_value' => $this->getSetting('width'),
      '#min' => 1,
    ];
    $form['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Height (px)'),
      '#default_value' => $this->getSetting('height'),
      '#min' => 1,
    ];
    $form['ignore_aspect_ratio'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ignore aspect ratio (&ignore)'),
      '#default_value' => $this->getSetting('ignore_aspect_ratio'),
    ];
    $form['dpi'] = [
      '#type' => 'number',
      '#title' => $this->t('DPI (for retina display support)'),
      '#default_value' => $this->getSetting('dpi'),
      '#min' => 1,
    ];
    $form['alt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default alt text'),
      '#default_value' => $this->getSetting('alt'),
    ];
    $form['url_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Output only the transformed URL'),
      '#default_value' => $this->getSetting('url_only'),
    ];
    $form['no_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable link wrapping'),
      '#default_value' => $this->getSetting('no_link'),
    ];
    $form['crop_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Crop width (px)'),
      '#default_value' => $this->getSetting('crop_width'),
      '#min' => 1,
    ];

    $form['crop_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Crop height (px)'),
      '#default_value' => $this->getSetting('crop_height'),
      '#min' => 1,
    ];

    $form['crop_gravity'] = [
      '#type' => 'select',
      '#title' => $this->t('Crop gravity'),
      '#default_value' => $this->getSetting('crop_gravity'),
      '#options' => [
        '' => $this->t('- None -'),
        'auto' => 'auto',
        'center' => 'center',
        'north' => 'north',
        'northeast' => 'northeast',
        'east' => 'east',
        'southeast' => 'southeast',
        'south' => 'south',
        'southwest' => 'southwest',
        'west' => 'west',
        'northwest' => 'northwest',
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];

    $summary[] = $this->t('Size: @w x @h', [
      '@w' => $this->getSetting('width'),
      '@h' => $this->getSetting('height'),
    ]);

    $summary[] = $this->getSetting('ignore_aspect_ratio')
    ? $this->t('Ignore aspect ratio: Yes')
    : $this->t('Ignore aspect ratio: No');

    $summary[] = $this->getSetting('dpi')
    ? $this->t('DPI: @dpi', ['@dpi' => $this->getSetting('dpi')])
    : $this->t('DPI: none');

    if ($this->getSetting('crop_width') && $this->getSetting('crop_height')) {
      $summary[] = $this->t('Crop: @w x @h (@g)', [
        '@w' => $this->getSetting('crop_width'),
        '@h' => $this->getSetting('crop_height'),
        '@g' => $this->getSetting('crop_gravity') ?: $this->t('none'),
      ]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $settings = $this->getSettings();
    $element = [];

    foreach ($items as $delta => $item) {
      $raw_url = $item->getUrl()?->toString();
      if (empty($raw_url) || !str_contains($raw_url, '/original/')) {
        continue;
      }

      $transformed_url = str_replace('/original/', $this->buildTransformPath($settings), $raw_url);

      // Handle 'url_only' mode: output raw URL and skip all rendering.
      if (!empty($settings['url_only'])) {
        $element[$delta] = ['#markup' => $transformed_url];
        continue;
      }

      $title = $item->title ?? '';
      $display_settings = [
        'width' => $settings['width'],
        'height' => $settings['height'],
        'ignore_aspect_ratio' => $settings['ignore_aspect_ratio'],
        'dpi' => $settings['dpi'],
        'alt' => $settings['alt'] ?: $title,
      ];

      $asset = Asset::createFromValues([
        'type' => 'image',
        'name' => $title,
        'remote_url' => $transformed_url,
      ]);

      $formatter = AssetFormatterManager::create($asset, $display_settings);
      $output = $formatter->format();

      if (!empty($settings['no_link'])) {
        $output['#attributes']['class'][] = 'img-fluid';
        $element[$delta] = $output;
      }
      else {
        $link = $item->getUrl();
        $link_options = $link->getOptions();
        unset($link_options['attributes']['ib_dam']);
        $link->setOptions($link_options);

        $element[$delta] = [
          '#type' => 'link',
          '#title' => $output,
          '#url' => $link,
        ];
      }
    }

    return $element;
  }

  /**
   * Builds a transformation path segment for the URL.
   */
  protected function buildTransformPath(array $settings): string {
    $query = [];
    if (!empty($settings['width'])) {
      $query['size'] = "{$settings['width']}";
    }
    if (!empty($settings['height'])) {
      $query['size'] .= "x{$settings['height']}";
    }
    if (!empty($settings['ignore_aspect_ratio'])) {
      $query['ignore'] = NULL;
    }
    $query['nolarge'] = NULL;
    if (!empty($settings['dpi'])) {
      $query['dpi'] = $settings['dpi'];
    }
    if (!empty($settings['crop_width'])) {
      $query['crop'] = "{$settings['crop_width']}";
    }
    if (!empty($settings['crop_height'])) {
      $query['crop'] .= "x{$settings['crop_height']}";
    }
    $query['crop'] .= "";
    if (!empty($settings['crop_gravity'])) {
      $query['cropgravity'] = $settings['crop_gravity'];
    }
    $transform_string = UrlHelper::buildQuery($query);
    return '/' . strtr($transform_string, ['&' => '&']) . '/';
  }

}
