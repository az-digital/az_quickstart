<?php

namespace Drupal\az_media_trellis\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\media_remote\Plugin\Field\FieldFormatter\MediaRemoteFormatterBase;
use Drupal\az_media_trellis\AzMediaTrellisService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'az_media_remote_trellis' formatter.
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
   * @var \Drupal\az_media_trellis\AzMediaTrellisService
   */
  protected $service;

  /**
   * AzMediaRemoteTrellisFormatter constructor.
   *
   * @param string $plugin_id
   *   The plugin ID for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third-party settings.
   * @param \Drupal\az_media_trellis\AzMediaTrellisService $service
   *   A custom service for with helpful functions for this media type.
   */
  public function __construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, AzMediaTrellisService $service) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->service = $service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('az_media_trellis')
    );
  }

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
      // This one field is the Trellis form URL.
      $url = $item->getValue()['value'];
      // e.g. https://forms-a.trellis.arizona.edu/185?tfa_4=7018N00000072edQAA
      // or https://forms-a.trellis.arizona.edu/<form_id>?tfa_4=<record_id>
      // Parse URL to remove query string.
      $parsedUrl = parse_url($url);
      $path = $parsedUrl['path'];
      // e.g. /185.
      // Insert 'publish' before '185', or the form_id more generally.
      $pathParts = explode('/', trim($path, '/'));
      $pathParts = array_merge(['publish'], $pathParts);

      // Reconstruct the new URL.
      $newPath = '/' . implode('/', $pathParts);
      $newUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $newPath;
      // e.g. https://forms-a.trellis.arizona.edu/publish/185
      $is_editing_context = $this->service::isEditingContext();

      // Retrieve the tfa_9 value (e.g., field_campaign_description).
      $media = $item->getEntity();
      $tfa_9 = NULL;
      if ($media->hasField('field_campaign_description') && !$media->get('field_campaign_description')->isEmpty()) {
        $tfa_9 = $media->get('field_campaign_description')->value;
      }
      $tfa_7 = NULL;
      if ($media->hasField('field_campaign_name') && !$media->get('field_campaign_name')->isEmpty()) {
        $tfa_7 = $media->get('field_campaign_name')->value;
      }
      $tfa_4 = NULL;
      if ($media->hasField('field_record_id') && !$media->get('field_record_id')->isEmpty()) {
        $tfa_4 = $media->get('field_record_id')->value;
      }

      $elements[$delta] = [
        '#theme' => 'az_media_trellis',
        '#url' => $newUrl,
        '#tfa_4' => $tfa_4,
        '#tfa_7' => $tfa_7,
        '#tfa_9' => $tfa_9,
        '#editing' => $is_editing_context,
      ];
    }
    return $elements;
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
    ];
  }

}
