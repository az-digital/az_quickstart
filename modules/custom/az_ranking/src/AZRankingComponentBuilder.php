<?php

namespace Drupal\az_ranking;

use Drupal\az_ranking\Plugin\Field\FieldType\AZRankingItem;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\Url;

/**
 * Turns stored ranking values into render arrays for the ranking components.
 *
 * Both page builder paths use it: AZRankingDefaultFormatter for the
 * published page, and AZRankingWidget for the live preview on the edit form.
 * Sharing it is what keeps the preview honest - the two cannot drift into
 * separate implementations of the same card.
 *
 * The build methods take a plain values array rather than an AZRankingItem.
 * Rationale: the formatter always holds a real item in the right order, but
 * the widget's preview does not. It has to survive drag-and-drop reordering
 * and AJAX rebuilds, so it reads from the Form API's #value, which reflects
 * the row the user is actually looking at, instead of $items[$delta], which
 * still holds the stored order and goes stale the moment a row moves.
 * Staying item-agnostic is what lets one builder serve both callers.
 */
class AZRankingComponentBuilder {

  /**
   * Background select values from the widget, mapped to our tokens.
   *
   * The keys look like CSS classes because that is literally what the widget
   * stores on the item, in options['class'] and options['hover_class'] - the
   * legacy template printed the setting straight into a class attribute. The
   * components take semantic values instead, so every one of these maps
   * translates on the way through.
   *
   * @see \Drupal\az_ranking\Plugin\Field\FieldWidget\AZRankingWidget
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
   * Font color select values from the widget, mapped to our tokens.
   */
  const FONT_COLOR_CLASS_MAP = [
    'ranking-text-midnight' => 'midnight',
    'ranking-text-black' => 'black',
    'ranking-text-white' => 'white',
    'ranking-text-az-blue' => 'az-blue',
  ];

  /**
   * Link style select values from the widget, mapped to our tokens.
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
   * Header style select values from the page builder, mapped to our tokens.
   *
   * @see \Drupal\az_paragraphs\Plugin\paragraphs\Behavior\AZRankingsParagraphBehavior
   */
  const HEADER_STYLE_MAP = [
    'ranking-title-thin' => 'thin',
    'ranking-title-bold' => 'bold',
    'ranking-title-bolder' => 'bolder',
  ];

  /**
   * Bootstrap column classes the page builder stores, mapped to counts.
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
   * This only translates stored values into props. How those props interact
   * - link title and style being ignored while the card is clickable, say -
   * is decided by ranking.twig's guards. Keeping it there means Canvas gets
   * the same behavior without going anywhere near this class.
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

    // If a link was stored, pass the link props through. Rationale: the
    // question here is whether the editor entered a link at all, which is
    // what the raw value answers. resolveLinkUrl() can return '' for a link
    // that was entered but no longer resolves - a path to a deleted node,
    // say - and those should still get link_title and link_style, so the
    // card renders consistently with any other unresolvable link.
    if (!empty($values['link_uri'])) {
      $props['link_url'] = $this->resolveLinkUrl($values['link_uri']);
      $props['link_title'] = $values['link_title'] ?? '';
      $props['link_style'] = self::LINK_STYLE_CLASS_MAP[$values['ranking_link_style'] ?? ''] ?? 'btn-red';
    }

    $props['header_style'] = self::HEADER_STYLE_MAP[$ranking_defaults['ranking_header_style'] ?? ''] ?? 'bold';
    $props['heading_font'] = ($ranking_defaults['ranking_heading_font'] ?? '') === 'serif' ? 'serif' : 'sans';
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
   * The `src` prop gets a plain file URI, because a component prop cannot
   * carry a render array. The az_ranking_responsive style still gets applied
   * - ranking-image.twig does it with az_media's image_style filter - so
   * scaling and WebP delivery match what #theme => image_formatter used to
   * produce. Focal point values ride along as props and are applied in the
   * browser by the component's own JS.
   *
   * Cache tags for the media and file are attached here because
   * AZRankingItem stores its media reference as a plain integer rather than
   * an entity reference, so nothing upstream contributes them. Without them,
   * swapping a media entity's image or moving its focal point would leave an
   * already-cached ranking showing the old one.
   *
   * The three width_span_* props are computed rather than passed straight
   * from the single stored column_span. Rationale: CSS Grid gives an item no
   * way to clamp its own span against its container's column count, so a
   * span of 4 in a 2-column deck grows an extra track and squeezes every
   * sibling in that row. Taking min() per breakpoint against the same
   * $deck_props the sibling ranking-deck receives reproduces what the legacy
   * template did, and stays correct for whatever the paragraph is actually
   * set to instead of capping at some safe guess. See ranking-image.css for
   * the spec-gap detail.
   *
   * @param array $values
   *   Normalized ranking item values - see extractItemValues().
   * @param array $deck_props
   *   The az_quickstart:ranking-deck props this item's parent deck will
   *   receive (columns_desktop/tablet/phone), from buildDeckProps().
   *
   * @see \Drupal\az_ranking\AZRankingImageHelper::getImageSourceAndFocalPoint()
   */
  public function buildImageComponent(array $values, array $deck_props): array {
    $stored_span = (int) ($values['options']['column_span'] ?? 2);
    $props = [
      'width_span_desktop' => (string) min($stored_span, (int) ($deck_props['columns_desktop'] ?? 4)),
      'width_span_tablet' => (string) min($stored_span, (int) ($deck_props['columns_tablet'] ?? 1)),
      'width_span_phone' => (string) min($stored_span, (int) ($deck_props['columns_phone'] ?? 1)),
    ];

    $cache_tags = [];
    if (!empty($values['media'])) {
      $media = $this->entityTypeManager->getStorage('media')->load($values['media']);
      if ($media) {
        // Tag on the media entity itself even when it turns out to carry no
        // image: both focal point values live on the media, so editing them
        // - or adding an image to a media entity that previously had none -
        // has to invalidate this render.
        $cache_tags = $media->getCacheTags();
        $image_data = $this->rankingImageHelper->getImageSourceAndFocalPoint($media);
        $cache_tags = Cache::mergeTags($cache_tags, $image_data['cache_tags']);
        if ($image_data['src'] !== '') {
          $props['src'] = $image_data['src'];
        }
        if ($image_data['focal_x'] !== NULL && $image_data['focal_y'] !== NULL) {
          $props['focal_x'] = $image_data['focal_x'];
          $props['focal_y'] = $image_data['focal_y'];
        }
      }
    }

    $build = [
      '#type' => 'component',
      '#component' => 'az_quickstart:ranking-image',
      '#props' => $props,
    ];
    if ($cache_tags) {
      $build['#cache']['tags'] = $cache_tags;
    }
    return $build;
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
   * Same resolution the formatter did before this port - public file links,
   * page anchors, and validated internal or external paths - but returned as
   * a string, because a component prop cannot hold the Url object a
   * #type => link render array wanted.
   */
  protected function resolveLinkUrl(string $link_uri): string {
    if ($link_uri === '') {
      return '';
    }

    if (str_starts_with($link_uri, '/' . PublicStream::basePath())) {
      return Url::fromUri(urldecode('base:' . $link_uri))->toString();
    }

    if (str_starts_with($link_uri, '#')) {
      // Keep an anchor as written, and turn a bare '#' into '#top'.
      // Rationale: two separate things go wrong with '#'. Url::fromUserInput
      // resolves it to an empty string, so routing it through Url loses the
      // anchor entirely. And the prop's own format: uri-reference validation
      // rejects a fragment with no name - '#top' passes where '#' does not.
      // Demo content uses '#' as a placeholder all over, so both would bite.
      // Neither goes anywhere either way; only the href text differs.
      return $link_uri === '#' ? '#top' : $link_uri;
    }

    $url = $this->pathValidator->getUrlIfValid($link_uri);
    return $url ? $url->toString() : '';
  }

}
