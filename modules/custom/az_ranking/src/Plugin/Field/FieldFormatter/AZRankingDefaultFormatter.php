<?php

namespace Drupal\az_ranking\Plugin\Field\FieldFormatter;

use Drupal\az_ranking\Plugin\Field\FieldType\AZRankingItem;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'az_ranking_default' formatter.
 *
 * Renders the field through the az_quickstart:ranking, az_quickstart:image,
 * and az_quickstart:ranking-deck Single Directory Components, so paragraph-
 * authored rankings and Canvas-composed rankings share the same markup.
 *
 * @see https://github.com/az-digital/az_quickstart/issues/5813
 */
#[FieldFormatter(
  id: 'az_ranking_default',
  label: new TranslatableMarkup('Default'),
  field_types: [
    'az_ranking',
  ],
)]
class AZRankingDefaultFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The AZRankingImageHelper service.
   *
   * @var \Drupal\az_ranking\AZRankingImageHelper
   */
  protected $rankingImageHelper;

  /**
   * Drupal\Core\Path\PathValidator definition.
   *
   * @var \Drupal\Core\Path\PathValidator
   */
  protected $pathValidator;

  /**
   * Legacy background/hover-background select values, keyed to SDC tokens.
   */
  const BACKGROUND_CLASS_MAP = [
    'text-bg-chili' => 'chili',
    'text-bg-blue' => 'blue',
    'bg-sky' => 'sky',
    'bg-oasis' => 'oasis',
    'text-bg-azurite' => 'azurite',
    'bg-cool-gray' => 'cool-gray',
    'bg-warm-gray' => 'warm-gray',
    'bg-white' => 'white',
    'bg-transparent' => 'transparent',
  ];

  /**
   * Legacy font color select values, keyed to SDC tokens.
   */
  const FONT_COLOR_CLASS_MAP = [
    'ranking-text-midnight' => 'midnight',
    'ranking-text-black' => 'black',
    'ranking-text-white' => 'white',
    'ranking-text-az-blue' => 'az-blue',
  ];

  /**
   * Legacy link style select values, keyed to SDC tokens.
   */
  const LINK_STYLE_CLASS_MAP = [
    'visually-hidden' => 'hidden',
    'link mt-2' => 'text-link',
    'w-100 btn btn-red mt-2' => 'btn-red',
    'w-100 btn btn-blue mt-2' => 'btn-blue',
    'w-100 btn btn-outline-red mt-2' => 'btn-outline-red',
    'w-100 btn btn-outline-blue mt-2' => 'btn-outline-blue',
    'w-100 btn btn-outline-white mt-2' => 'btn-outline-white',
  ];

  /**
   * Legacy per-breakpoint Bootstrap column classes, keyed to column counts.
   */
  const DESKTOP_COLUMN_MAP = [
    'col-lg-12' => '1',
    'col-lg-6' => '2',
    'col-lg-4' => '3',
    'col-lg-3' => '4',
  ];
  const TABLET_COLUMN_MAP = [
    'col-md-12' => '1',
    'col-md-6' => '2',
    'col-md-4' => '3',
    'col-md-3' => '4',
  ];
  const PHONE_COLUMN_MAP = [
    'col-12' => '1',
    'col-6' => '2',
    'col-4' => '3',
    'col-3' => '4',
  ];

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition,
    );

    $instance->rankingImageHelper = $container->get('az_ranking.image');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->pathValidator = $container->get('path.validator');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return ['interactive_links' => TRUE] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();

    $element['interactive_links'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Interactive Links'),
      '#default_value' => $settings['interactive_links'],
      '#description' => $this->t('If set, ranking links are clickable. Uncheck this setting to disable all ranking links. A common use-case is on the "Preview" view mode to prevent users from losing edit data if accidentally clicking on rankings from the edit form.'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();

    $interactive = 'No';
    if (!empty($settings['interactive_links'])) {
      $interactive = 'Yes';
    }
    $summary[] = $this->t('Interactive: @interactive', ['@interactive' => $interactive]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $rankings = [];

    // Computed before the loop (not after, as an earlier version of this
    // method did) because buildImageComponent() needs the deck's actual
    // per-breakpoint column counts to clamp each image's width_span_* props
    // against them - see that method's docblock for why this matters.
    $deck_props = [];
    $parent = $items->getEntity();
    if ($parent instanceof ParagraphInterface) {
      $behavior_settings = $parent->getAllBehaviorSettings();
      $deck_props = $this->buildDeckProps($behavior_settings['az_rankings_paragraph_behavior'] ?? []);
    }

    foreach ($items as $item) {
      assert($item instanceof AZRankingItem);
      $ranking_type = $item->options['ranking_type'] ?? 'standard';
      $rankings[] = $ranking_type === 'image_only'
        ? $this->buildImageComponent($item, $deck_props)
        : $this->buildRankingComponent($item);
    }

    return [
      0 => [
        '#type' => 'component',
        '#component' => 'az_quickstart:ranking-deck',
        '#props' => $deck_props,
        '#slots' => ['rankings' => $rankings],
      ],
    ];
  }

  /**
   * Builds an az_quickstart:ranking component render array for one item.
   *
   * Props like clickable/hover_effect/link_style interactions (e.g. link
   * title and style being ignored while clickable) are NOT resolved here —
   * ranking.twig's own guards are the single source of truth for that
   * behavior, so this only needs to map field/behavior values onto clean
   * prop values.
   */
  protected function buildRankingComponent(AZRankingItem $item): array {
    $props = [
      'heading' => $item->ranking_heading ?? '',
      'description' => $item->ranking_description ?? '',
      'source' => $item->ranking_source ?? '',
    ];

    // Gate on the RAW stored link_uri, not the resolved URL string — a bare
    // '#' (a common placeholder in demo content) is a real, present link
    // that legacy always showed a button for, but Url::fromUserInput('#')
    // legitimately stringifies to '' (confirmed empirically, not assumed).
    // Checking the resolved string's emptiness instead of the source value
    // silently dropped every ranking using such a placeholder link.
    if (!empty($item->link_uri)) {
      $props['link_url'] = $this->resolveLinkUrl($item->link_uri);
      $props['link_title'] = $item->link_title ?? '';
      $props['link_style'] = self::LINK_STYLE_CLASS_MAP[$item->ranking_link_style ?? ''] ?? 'btn-red';
    }

    $parent = $item->getEntity();
    if ($parent instanceof ParagraphInterface) {
      $behavior_settings = $parent->getAllBehaviorSettings();
      $ranking_defaults = $behavior_settings['az_rankings_paragraph_behavior'] ?? [];
      $props['header_style'] = ($ranking_defaults['ranking_header_style'] ?? '') === 'ranking-title-thin' ? 'thin' : 'bold';
      $props['alignment'] = ($ranking_defaults['ranking_alignment'] ?? '') === 'text-center' ? 'center' : 'left';
      $props['clickable'] = !empty($ranking_defaults['ranking_clickable']);
      $props['hover_effect'] = !empty($ranking_defaults['ranking_hover_effect']);
    }

    $background_class = $item->options['class'] ?? '';
    $props['background'] = self::BACKGROUND_CLASS_MAP[$background_class] ?? 'chili';
    $props['hover_background'] = self::BACKGROUND_CLASS_MAP[$item->options['hover_class'] ?? ''] ?? 'chili';
    if ($background_class === 'bg-transparent') {
      $props['font_color'] = self::FONT_COLOR_CLASS_MAP[$item->ranking_font_color ?? ''] ?? 'midnight';
    }

    return [
      '#type' => 'component',
      '#component' => 'az_quickstart:ranking',
      '#props' => $props,
    ];
  }

  /**
   * Builds an az_quickstart:image component render array for one item.
   *
   * Unlike the legacy #theme => image_formatter path, this does not apply
   * the az_ranking_responsive image style or the custom focal-point JS
   * positioning — az_quickstart:image takes a plain file URI, not a themed
   * render array, and there is no field/prop for either capability. This is
   * a known, disclosed gap versus the legacy image_only rendering, not an
   * oversight.
   *
   * width_span_desktop/tablet/phone are computed here, not just passed
   * through legacy's single column_span value, because CSS Grid cannot
   * clamp a span against its container's actual column count (a confirmed
   * CSS spec gap, not a browser quirk - see image.css's own docblock and
   * https://github.com/w3c/csswg-drafts/issues/5852). This reproduces
   * legacy's own "min(current row width, column_span)" behavior exactly,
   * per breakpoint, using the SAME $deck_props the sibling ranking-deck
   * component receives, so the clamp is always correct for whatever the
   * paragraph is actually configured to - not a fixed, conservative cap.
   *
   * @param \Drupal\az_ranking\Plugin\Field\FieldType\AZRankingItem $item
   *   The field item to build a component for.
   * @param array $deck_props
   *   The az_quickstart:ranking-deck props this item's parent deck will
   *   receive (columns_desktop/tablet/phone), from buildDeckProps().
   *
   * @see \Drupal\az_ranking\AZRankingImageHelper::getImageSourceAndAlt()
   */
  protected function buildImageComponent(AZRankingItem $item, array $deck_props): array {
    $legacy_span = (int) ($item->options['column_span'] ?? 2);
    $props = [
      'width_span_desktop' => (string) min($legacy_span, (int) ($deck_props['columns_desktop'] ?? 4)),
      'width_span_tablet' => (string) min($legacy_span, (int) ($deck_props['columns_tablet'] ?? 1)),
      'width_span_phone' => (string) min($legacy_span, (int) ($deck_props['columns_phone'] ?? 1)),
    ];

    if (!empty($item->media)) {
      $media = $this->entityTypeManager->getStorage('media')->load($item->media);
      if ($media) {
        $image_data = $this->rankingImageHelper->getImageSourceAndAlt($media);
        if ($image_data['src'] !== '') {
          $props['src'] = $image_data['src'];
          $props['alt'] = $image_data['alt'];
        }
      }
    }

    return [
      '#type' => 'component',
      '#component' => 'az_quickstart:image',
      '#props' => $props,
    ];
  }

  /**
   * Maps the parent paragraph's per-breakpoint column settings to deck props.
   */
  protected function buildDeckProps(array $ranking_defaults): array {
    $az_display_settings = $ranking_defaults['az_display_settings'] ?? [];
    return [
      'columns_desktop' => self::DESKTOP_COLUMN_MAP[$ranking_defaults['ranking_width'] ?? ''] ?? '4',
      'columns_tablet' => self::TABLET_COLUMN_MAP[$az_display_settings['ranking_width_sm'] ?? ''] ?? '1',
      'columns_phone' => self::PHONE_COLUMN_MAP[$az_display_settings['ranking_width_xs'] ?? ''] ?? '1',
    ];
  }

  /**
   * Resolves a stored link_uri value to a plain URL string, or ''.
   *
   * Mirrors the URL resolution the legacy formatter already performed
   * (public file links, page anchors, and validated internal/external
   * paths), only stringified for use as an SDC prop value instead of being
   * kept as a Url object for a #type => link render array.
   */
  protected function resolveLinkUrl(string $link_uri): string {
    if ($link_uri === '') {
      return '';
    }

    if (str_starts_with($link_uri, '/' . PublicStream::basePath())) {
      return Url::fromUri(urldecode('base:' . $link_uri))->toString();
    }

    if (str_starts_with($link_uri, '#')) {
      // Url::fromUserInput('#') is valid but its ->toString() legitimately
      // returns '' for a bare fragment (confirmed empirically) - preserve
      // the literal anchor directly instead of losing it. A BARE '#' (no
      // fragment name) is also rejected by the SDC prop's own
      // format: uri-reference validation (confirmed empirically: '#top'
      // passes, '#' alone does not) - normalize the empty-fragment case to
      // a named one so common placeholder links ('#', used throughout demo
      // content) don't fail validation. Same practical behavior (no real
      // destination); only the literal href text differs from legacy's '#'.
      return $link_uri === '#' ? '#top' : $link_uri;
    }

    $url = $this->pathValidator->getUrlIfValid($link_uri);
    return $url ? $url->toString() : '';
  }

}
