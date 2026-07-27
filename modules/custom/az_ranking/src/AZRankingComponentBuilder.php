<?php

namespace Drupal\az_ranking;

use Drupal\az_ranking\Plugin\Field\FieldType\AZRankingItem;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\Url;

/**
 * Maps normalized ranking item values to az_quickstart SDC render arrays.
 *
 * Shared by AZRankingDefaultFormatter (published rendering) and
 * AZRankingWidget (the live edit-form preview), so both render through the
 * exact same components and can't drift apart into two implementations.
 *
 * buildRankingComponent()/buildImageComponent() take a plain values array
 * rather than an AZRankingItem directly. AZRankingDefaultFormatter always
 * has a real, correctly-ordered AZRankingItem and uses extractItemValues()
 * to build that array. AZRankingWidget's live preview needs to stay correct
 * through drag-and-drop reorder + AJAX rebuilds, which means reading values
 * from the Form API's own #value (populated from user input, always correct
 * for the current row) instead of $items[$delta] (which reflects stored
 * array order and can drift out of sync with the visual row after a
 * reorder) - so it builds this same array from form state instead of an
 * item. Keeping the builder itself item-agnostic is what lets both callers
 * share it.
 */
class AZRankingComponentBuilder {

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
   * The path validator service.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * Constructs a new AZRankingComponentBuilder object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AZRankingImageHelper $ranking_image_helper, PathValidatorInterface $path_validator) {
    $this->entityTypeManager = $entity_type_manager;
    $this->rankingImageHelper = $ranking_image_helper;
    $this->pathValidator = $path_validator;
  }

  /**
   * Extracts a plain values array from a real, hydrated AZRankingItem.
   *
   * The single source of truth for "how does a stored ranking item map onto
   * the values array buildRankingComponent()/buildImageComponent() expect."
   * AZRankingWidget's live preview builds this same shape itself, from
   * Form API #value instead of an item - see this class's own docblock.
   */
  public function extractItemValues(AZRankingItem $item): array {
    return [
      'ranking_heading' => $item->ranking_heading ?? '',
      'ranking_description' => $item->ranking_description ?? '',
      'ranking_source' => $item->ranking_source ?? '',
      'link_uri' => $item->link_uri ?? '',
      'link_title' => $item->link_title ?? '',
      'ranking_link_style' => $item->ranking_link_style ?? '',
      'ranking_font_color' => $item->ranking_font_color ?? '',
      'media' => $item->media ?? NULL,
      'options' => is_array($item->options) ? $item->options : [],
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
   *
   * @param array $values
   *   Normalized ranking item values - see extractItemValues().
   * @param array $ranking_defaults
   *   The parent paragraph's az_rankings_paragraph_behavior settings.
   */
  public function buildRankingComponent(array $values, array $ranking_defaults): array {
    $props = [
      'heading' => $values['ranking_heading'] ?? '',
      'description' => $values['ranking_description'] ?? '',
      'source' => $values['ranking_source'] ?? '',
    ];

    // Gate on the RAW stored link_uri, not the resolved URL string — a bare
    // '#' (a common placeholder in demo content) is a real, present link
    // that legacy always showed a button for, but Url::fromUserInput('#')
    // legitimately stringifies to '' (confirmed empirically, not assumed).
    // Checking the resolved string's emptiness instead of the source value
    // silently dropped every ranking using such a placeholder link.
    if (!empty($values['link_uri'])) {
      $props['link_url'] = $this->resolveLinkUrl($values['link_uri']);
      $props['link_title'] = $values['link_title'] ?? '';
      $props['link_style'] = self::LINK_STYLE_CLASS_MAP[$values['ranking_link_style'] ?? ''] ?? 'btn-red';
    }

    $props['header_style'] = ($ranking_defaults['ranking_header_style'] ?? '') === 'ranking-title-thin' ? 'thin' : 'bold';
    $props['alignment'] = ($ranking_defaults['ranking_alignment'] ?? '') === 'text-center' ? 'center' : 'left';
    $props['clickable'] = !empty($ranking_defaults['ranking_clickable']);
    $props['hover_effect'] = !empty($ranking_defaults['ranking_hover_effect']);

    $background_class = $values['options']['class'] ?? '';
    $props['background'] = self::BACKGROUND_CLASS_MAP[$background_class] ?? 'chili';
    $props['hover_background'] = self::BACKGROUND_CLASS_MAP[$values['options']['hover_class'] ?? ''] ?? 'chili';
    if ($background_class === 'bg-transparent') {
      $props['font_color'] = self::FONT_COLOR_CLASS_MAP[$values['ranking_font_color'] ?? ''] ?? 'midnight';
    }

    return [
      '#type' => 'component',
      '#component' => 'az_quickstart:ranking',
      '#props' => $props,
    ];
  }

  /**
   * Builds an az_quickstart:ranking-image component render array for one item.
   *
   * Unlike the legacy #theme => image_formatter path, this does not apply
   * the az_ranking_responsive image style — az_quickstart:ranking-image
   * takes a plain file URI, not a themed render array, so server-side image
   * style processing is a known, disclosed gap versus the legacy image_only
   * rendering, not an oversight. Focal point data IS passed through (see
   * AZRankingImageHelper::getImageSourceAltAndFocalPoint()), so
   * focal-point-aware cropping works client-side via the ranking-image
   * SDC's own JS.
   *
   * width_span_desktop/tablet/phone are computed here, not just passed
   * through legacy's single column_span value, because CSS Grid cannot
   * clamp a span against its container's actual column count (a confirmed
   * CSS spec gap, not a browser quirk - see ranking-image.css's own
   * docblock and
   * https://github.com/w3c/csswg-drafts/issues/5852). This reproduces
   * legacy's own "min(current row width, column_span)" behavior exactly,
   * per breakpoint, using the SAME $deck_props the sibling ranking-deck
   * component receives, so the clamp is always correct for whatever the
   * paragraph is actually configured to - not a fixed, conservative cap.
   *
   * @param array $values
   *   Normalized ranking item values - see extractItemValues().
   * @param array $deck_props
   *   The az_quickstart:ranking-deck props this item's parent deck will
   *   receive (columns_desktop/tablet/phone), from buildDeckProps().
   *
   * @see \Drupal\az_ranking\AZRankingImageHelper::getImageSourceAltAndFocalPoint()
   */
  public function buildImageComponent(array $values, array $deck_props): array {
    $legacy_span = (int) ($values['options']['column_span'] ?? 2);
    $props = [
      'width_span_desktop' => (string) min($legacy_span, (int) ($deck_props['columns_desktop'] ?? 4)),
      'width_span_tablet' => (string) min($legacy_span, (int) ($deck_props['columns_tablet'] ?? 1)),
      'width_span_phone' => (string) min($legacy_span, (int) ($deck_props['columns_phone'] ?? 1)),
    ];

    if (!empty($values['media'])) {
      $media = $this->entityTypeManager->getStorage('media')->load($values['media']);
      if ($media) {
        $image_data = $this->rankingImageHelper->getImageSourceAltAndFocalPoint($media);
        if ($image_data['src'] !== '') {
          $props['src'] = $image_data['src'];
          $props['alt'] = $image_data['alt'];
        }
        if ($image_data['focal_x'] !== NULL && $image_data['focal_y'] !== NULL) {
          $props['focal_x'] = $image_data['focal_x'];
          $props['focal_y'] = $image_data['focal_y'];
          $props['original_width'] = $image_data['original_width'];
          $props['original_height'] = $image_data['original_height'];
        }
      }
    }

    return [
      '#type' => 'component',
      '#component' => 'az_quickstart:ranking-image',
      '#props' => $props,
    ];
  }

  /**
   * Maps the parent paragraph's per-breakpoint column settings to deck props.
   */
  public function buildDeckProps(array $ranking_defaults): array {
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
