<?php

namespace Drupal\az_media_trellis\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\media_remote\Plugin\Field\FieldFormatter\MediaRemoteFormatterBase;
use Drupal\az_media_trellis\AzMediaTrellisService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field formatter for rendering Trellis forms as remote media.
 *
 * This formatter handles the display of FormAssembly Trellis forms embedded
 * within Drupal content as remote media entities. It provides comprehensive
 * support for:
 * - URL validation and transformation for FormAssembly Quick Publish format
 * - Context-aware rendering (editing vs viewing modes)
 * - Responsive sizing based on view modes
 * - Query parameter preservation for form prefilling
 * - Security validation of Trellis URLs
 * - Caching optimization for performance.
 *
 * The formatter transforms standard Trellis form URLs into the Quick Publish
 * format required for JavaScript embedding:
 * - Input: https://forms-a.trellis.arizona.edu/185?tfa_4=value
 * - Output: https://forms-a.trellis.arizona.edu/publish/185
 * - Input: https://trellis.tfaforms.net/72?tfa_4=value
 * - Output: https://trellis.tfaforms.net/publish/72
 *
 * @see https://help.formassembly.com/help/javascript-form-publishing
 * @see \Drupal\media_remote\Plugin\Field\FieldFormatter\MediaRemoteFormatterBase
 * @see \Drupal\az_media_trellis\AzMediaTrellisService
 */
#[FieldFormatter(
  id: 'az_media_remote_trellis',
  label: new TranslatableMarkup('Remote Media - Trellis Form'),
  description: new TranslatableMarkup('Renders FormAssembly Trellis forms with responsive sizing, context-aware behavior, and query parameter support.'),
  field_types: [
    'string',
  ],
)]
class AzMediaRemoteTrellisFormatter extends MediaRemoteFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The AZ Media Trellis service for context detection and URL validation.
   *
   * @var \Drupal\az_media_trellis\AzMediaTrellisService
   */
  protected AzMediaTrellisService $trellisService;

  /**
   * Constructs an AzMediaRemoteTrellisFormatter object.
   *
   * @param string $plugin_id
   *   The plugin ID for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\az_media_trellis\AzMediaTrellisService $trellis_service
   *   The AZ Media Trellis service for context detection, URL validation,
   *   and utility functions specific to Trellis form integration.
   */
  public function __construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, AzMediaTrellisService $trellis_service) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->trellisService = $trellis_service;
  }

  /**
   * {@inheritdoc}
   *
   * Creates a new instance of the formatter with dependency injection.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   A new instance of this formatter.
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
   *
   * Returns the regex pattern for validating Trellis form URLs.
   *
   * This pattern validates URLs from both supported Trellis (FormAssembly)
   * instances, ensuring they follow the expected format:
   * - University of Arizona: https://forms-a.trellis.arizona.edu/[form_id]
   * - TFA Forms: https://trellis.tfaforms.net/[form_id]
   *
   * @return string
   *   A regular expression pattern for matching valid Trellis URLs from
   *   both supported domains.
   */
  public static function getUrlRegexPattern() {
    return '/^https:\/\/(forms-a\.trellis\.arizona\.edu|trellis\.tfaforms\.net)\/([0-9]+)/';
  }

  /**
   * {@inheritdoc}
   *
   * Provides example URLs that are valid for this formatter.
   *
   * These examples demonstrate the expected URL format for Trellis forms
   * from both supported domains, including both simple form URLs and URLs
   * with query parameters for form prefilling (e.g., Salesforce record IDs).
   *
   * @return array
   *   An array of example valid URLs for documentation and testing purposes.
   */
  public static function getValidUrlExampleStrings(): array {
    return [
      // University of Arizona Trellis forms.
      'https://forms-a.trellis.arizona.edu/192',
      'https://forms-a.trellis.arizona.edu/185?tfa_4=7018N00000072edQAA',
      'https://forms-a.trellis.arizona.edu/185?tfa_4=7018N00000071eDQAQ',
      // TFA Forms Trellis forms.
      'https://trellis.tfaforms.net/72',
      // 'https://trellis.tfaforms.net/85?tfa_4=7013n000002QlNS',
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Derives a default media name from a Trellis form URL.
   *
   * Extracts the form ID from the URL and creates a human-readable name
   * for the media entity. Works with both supported Trellis domains:
   * - University of Arizona: forms-a.trellis.arizona.edu
   * - TFA Forms: trellis.tfaforms.net
   *
   * If the URL doesn't match the expected pattern, falls back to the
   * parent class implementation.
   *
   * @param string $url
   *   The Trellis form URL to derive a name from.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   A translatable markup object containing the derived media name,
   *   or the parent class result if URL parsing fails.
   */
  public static function deriveMediaDefaultNameFromUrl($url) {
    $pattern = static::getUrlRegexPattern();
    if (preg_match($pattern, $url, $matches)) {
      return t('Trellis Form at @url', [
        '@url' => $url,
      ]);
    }
    return parent::deriveMediaDefaultNameFromUrl($url);
  }

  /**
   * {@inheritdoc}
   *
   * Builds a renderable array for field items.
   *
   * This method transforms Trellis form URLs into the FormAssembly Quick
   * Publish format and creates themed render elements for each field item. The
   * process involves:
   *
   * 1. URL Transformation: Converts standard Trellis URLs to Quick Publish
   *    format
   *    - Input: https://forms-a.trellis.arizona.edu/185?params
   *    - Output: https://forms-a.trellis.arizona.edu/publish/185
   *    - Input: https://trellis.tfaforms.net/72?params
   *    - Output: https://trellis.tfaforms.net/publish/72
   *
   * 2. Context Detection: Determines if the current environment is an
   *    editing context (node edit, media library, etc.) to adjust form
   *    behavior
   *
   * 3. View Mode Integration: Passes the current view mode for responsive
   *    sizing and appropriate display formatting
   *
   * 4. Caching: Implements appropriate cache contexts and max-age for
   *    performance optimization
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field items to be rendered.
   * @param string $langcode
   *   The language that should be used to render the field.
   *
   * @return array
   *   A renderable array of themed elements for each field item.
   *
   * @see \Drupal\az_media_trellis\AzMediaTrellisService::isEditingContext()
   * @see az-media-trellis.html.twig
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      /** @var \Drupal\Core\Field\FieldItemInterface $item */
      if ($item->isEmpty()) {
        continue;
      }

      // Extract the Trellis form URL from the field item.
      $url = $item->getValue()['value'];
      // Examples: https://forms-a.trellis.arizona.edu/185?tfa_4=value
      // https://trellis.tfaforms.net/72?tfa_4=value
      // Transform URL to FormAssembly Quick Publish format.
      // Parse URL to extract components while preserving query parameters.
      $parsedUrl = parse_url($url);
      // e.g., '/185'.
      $path = $parsedUrl['path'];

      // Insert 'publish' before the form ID for Quick Publish format.
      $pathParts = explode('/', trim($path, '/'));
      $pathParts = array_merge(['publish'], $pathParts);

      // Reconstruct the URL for JavaScript embedding.
      $newPath = '/' . implode('/', $pathParts);
      $newUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $newPath;
      // Results: https://forms-a.trellis.arizona.edu/publish/185
      // https://trellis.tfaforms.net/publish/72
      // Determine current context for form behavior modification.
      $is_editing_context = $this->trellisService->isEditingContext();

      // Get the view mode for responsive sizing and display options.
      $view_mode = $this->configuration['view_mode'] ?? $this->viewMode ?? 'default';

      // Build themed render element with comprehensive metadata.
      $target_id = Html::getUniqueId('az-media-trellis');
      $elements[$delta] = [
        '#theme' => 'az_media_trellis',
        '#url' => $newUrl,
        '#editing' => $is_editing_context,
        '#view_mode' => $view_mode,
        '#target_id' => $target_id,
        '#cache' => [
          // Cache varies by URL query arguments for form prefilling.
          'contexts' => ['url.query_args'],
          // Cache for 1 hour to balance performance and content freshness.
          'max-age' => 3600,
        ],
      ];
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   *
   * Defines the settings form for this formatter.
   *
   * Extends the parent settings form with Trellis-specific configuration
   * options. Currently provides a URL field for manual URL entry and
   * validation during field configuration.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The modified form array with additional settings fields.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return parent::settingsForm($form, $form_state) + [
      'url' => [
        '#type' => 'string',
        '#title' => $this->t('URL'),
        '#size' => 255,
        '#maxlength' => 255,
        '#description' => $this->t('The URL of the Trellis form. Must be a valid FormAssembly Trellis URL from forms-a.trellis.arizona.edu or trellis.tfaforms.net.'),
      ],
    ];
  }

}
