<?php

namespace Drupal\az_media_trellis\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Template\Attribute;
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
    return '/^https:\/\/(forms-a\.trellis\.arizona\.edu|trellis\.tfaforms\.net)\/(?:[0-9]+|f\/[^\/?#]+)/';
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
      'https://forms-a.trellis.arizona.edu/f/campaign-embed-stage',
      'https://forms-a.trellis.arizona.edu/185?tfa_4=7018N00000072edQAA',
      'https://forms-a.trellis.arizona.edu/185?tfa_4=7018N00000071eDQAQ',
      // TFA Forms Trellis forms.
      'https://trellis.tfaforms.net/72',
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
    $is_editing_context = $this->trellisService->isEditingContext();
    $view_mode = $this->configuration['view_mode'] ?? $this->viewMode ?? 'default';
    $entity = $items->getEntity();

    foreach ($items as $delta => $item) {
      if ($item->isEmpty()) {
        continue;
      }

      $raw_url = $item->getValue()['value'];
      $query_params = [];
      $quick_publish_url = $this->transformToQuickPublishUrl($raw_url, $query_params);

      // Skip invalid URLs silently (parent base class already validated on
      // save, but guards here protect against legacy data).
      if ($quick_publish_url === NULL) {
        continue;
      }

      // Editing context: lightweight placeholder only, no remote script.
      if ($is_editing_context) {
        $elements[$delta] = $this->buildEditingPlaceholder(
          $entity->label(),
          $view_mode
        );
        continue;
      }

      // View context: build themed render array.
      $unique_id = Html::getUniqueId('az-media-trellis');
      $attr = new Attribute([
        'id' => $unique_id,
        'class' => [
          Html::getClass('az-media-trellis'),
          Html::getClass('az-media-trellis--' . $view_mode),
        ],
        'data-query-params' => $query_params ? json_encode($query_params) : '{}',
        'data-editing' => 'false',
        'data-trellis-embed-src' => $quick_publish_url,
        'data-trellis-script-preloaded' => '1',
      ]);

      $elements[$delta] = [
        '#theme' => 'az_media_trellis',
        '#editing' => FALSE,
        '#url' => $quick_publish_url,
        '#attributes' => $attr,
        '#cache' => [
          'contexts' => [
            // Vary by query args since they influence prefill behavior.
            'url.query_args',
            // Vary by interface language so label / potential localized
            // content remains accurate if extended.
            'languages:language_interface',
          ],
          'tags' => $entity->getCacheTags(),
          'max-age' => 3600,
        ],
      ];

      // CSS blocker script must precede remote embed script.
      $elements[$delta]['#attached']['html_head'][] = [
        [
          '#tag' => 'script',
          '#value' => "(function(){if(window.__azTrellisCssBlockerInstalled)return;window.__azTrellisCssBlockerInstalled=true;var P=[/design\\.trellis\\.arizona\\.edu\\/css\\/form-assembly\\.css/i,/forms-a\\.trellis\\.arizona\\.edu\\/dist\\/form-builder\\//i,/forms-a\\.trellis\\.arizona\\.edu\\/uploads\\/themes\\//i,/forms-a\\.trellis\\.arizona\\.edu\\/wForms\\/3\\.11\\/css\\//i];function blocked(n){if(!n||n.tagName!=='LINK'||n.rel!=='stylesheet')return false;return P.some(function(rx){return rx.test(n.href);});}function wrapAppend(orig){return function(node){try{if(blocked(node))return node;}catch(e){}return orig.call(this,node);};}function wrapInsertBefore(orig){return function(node,ref){try{if(blocked(node))return node;}catch(e){}return orig.call(this,node,ref);};}var d=document.constructor.prototype,h=HTMLHeadElement.prototype;if(!d.__azTrellisPatched){d.__azTrellisPatched=true;d.appendChild=wrapAppend(d.appendChild);}if(!h.__azTrellisPatchedA){h.__azTrellisPatchedA=true;h.appendChild=wrapAppend(h.appendChild);}if(!h.__azTrellisPatchedIB){h.__azTrellisPatchedIB=true;h.insertBefore=wrapInsertBefore(h.insertBefore);}new MutationObserver(function(mutations){mutations.forEach(function(mutation){mutation.addedNodes.forEach(function(node){if(blocked(node)){if(node.parentNode){node.parentNode.removeChild(node);}}});});}).observe(document.head,{childList:true});})();",
        ],
        'az_media_trellis_css_blocker_' . $unique_id,
      ];

      $elements[$delta]['#attached']['html_head'][] = [
        [
          '#tag' => 'script',
          '#attributes' => [
            'src' => $quick_publish_url,
            'type' => 'text/javascript',
            'defer' => 'defer',
            'data-qp-target-id' => $unique_id,
          ],
        ],
        'trellis_embed_script_' . $unique_id,
      ];
    }

    if (!empty($elements) && !$is_editing_context) {
      $last_key = array_key_last($elements);
      $elements[$last_key]['#attached']['library'][] = 'az_media_trellis/az-media-trellis';
      $elements[$last_key]['#attached']['drupalSettings']['azMediaTrellis']['blockRemoteCss'] = TRUE;
    }

    return $elements;
  }

  /**
   * Convert a Trellis form URL to its Quick Publish variant.
   *
   * Populates $query_params with any original query parameters for later
   * embedding as JSON (prefill support). Returns NULL if URL does not match
   * the expected Trellis pattern.
   */
  protected function transformToQuickPublishUrl(string $url, array &$query_params = []): ?string {
    $pattern = static::getUrlRegexPattern();
    if (!preg_match($pattern, $url, $matches)) {
      return NULL;
    }
    $parsed = parse_url($url);
    if (!$parsed || empty($parsed['scheme']) || empty($parsed['host']) || empty($parsed['path'])) {
      return NULL;
    }
    if (!empty($parsed['query'])) {
      parse_str($parsed['query'], $query_params);
    }
    $parts = explode('/', trim($parsed['path'], '/'));
    if (!empty($parts) && $parts[0] === 'publish') {
      $new_path = '/' . implode('/', $parts);
      return $parsed['scheme'] . '://' . $parsed['host'] . $new_path;
    }
    if (count($parts) >= 2 && $parts[0] === 'f') {
      $slug_url = $parsed['scheme'] . '://' . $parsed['host'] . '/f/' . $parts[1];
      $form_id = $this->resolveSlugToFormId($slug_url);
      if ($form_id === NULL) {
        return NULL;
      }
      $parts = ['publish', $form_id];
    }
    else {
      $parts = array_merge(['publish'], $parts);
    }
    $new_path = '/' . implode('/', $parts);
    return $parsed['scheme'] . '://' . $parsed['host'] . $new_path;
  }

  /**
   * Resolve a Trellis custom URL slug to its numeric form ID.
   */
  protected function resolveSlugToFormId(string $slug_url): ?string {
    $cid = 'az_media_trellis:slug:' . hash('sha256', $slug_url);
    $cache = \Drupal::cache('default');
    if ($cached = $cache->get($cid)) {
      return $cached->data ?: NULL;
    }

    try {
      $response = \Drupal::httpClient()->get($slug_url, [
        'timeout' => 5,
        'headers' => [
          'User-Agent' => 'Drupal az_media_trellis',
        ],
      ]);
      if ($response->getStatusCode() !== 200) {
        $cache->set($cid, NULL,
          \Drupal::time()->getRequestTime() + 3600);
        return NULL;
      }
      $html = (string) $response->getBody();
      if (preg_match('/id="(\d+)-WRPR"/', $html, $match)
        || preg_match('/<form[^>]+id="(\d+)"/i', $html, $match)) {
        $cache->set($cid, $match[1], Cache::PERMANENT);
        return $match[1];
      }
    }
    catch (\Exception $e) {
      // Fail silently; caller will treat as invalid URL.
    }

    $cache->set($cid, NULL, \Drupal::time()->getRequestTime() + 3600);
    return NULL;
  }

  /**
   * Build the lightweight edit-mode placeholder render array.
   */
  protected function buildEditingPlaceholder(string $label, string $view_mode): array {
    $id = Html::getUniqueId('az-media-trellis-preview');
    return [
      '#type' => 'container',
      '#attributes' => [
        'id' => $id,
        'class' => [
          Html::getClass('az-media-trellis-placeholder'),
          Html::getClass('az-media-trellis-placeholder--' . $view_mode),
        ],
        'data-editing' => 'true',
        'role' => 'img',
        'aria-label' => $label,
      ],
      'label' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $label,
        '#attributes' => [
          'class' => ['az-media-trellis-placeholder__label'],
        ],
      ],
      '#attached' => [
        'library' => [
          'az_media_trellis/az-media-trellis.styles',
        ],
      ],
    ];
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
        '#size' => 60,
        '#maxlength' => 1024,
        '#description' => $this->t('The URL of the Trellis form. Must be a valid FormAssembly Trellis URL from forms-a.trellis.arizona.edu or trellis.tfaforms.net.'),
      ],
    ];
  }

}
