<?php

namespace Drupal\az_canvas_migrate;

use Drupal\az_ranking\AZRankingComponentBuilder;
use Drupal\az_ranking\Plugin\Field\FieldType\AZRankingItem;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Builds Canvas component subtrees from Paragraphs.
 *
 * Used by the per-paragraph-type migrations, such as
 * az_paragraph_rankings_to_canvas. az_rankings paragraphs become an
 * az_quickstart:ranking-deck with ranking/ranking-image children, built from
 * the same AZRankingComponentBuilder props the paragraph formatter renders,
 * so both pages produce the same markup.
 *
 * Canvas classes are deliberately not referenced, so this code can be
 * analyzed without Canvas installed.
 */
class CanvasComponentTreeBuilder {

  use StringTranslationTrait;

  /**
   * Canvas component config entity IDs for the ranking SDCs.
   */
  const DECK_COMPONENT = 'sdc.az_quickstart.ranking-deck';
  const RANKING_COMPONENT = 'sdc.az_quickstart.ranking';
  const IMAGE_COMPONENT = 'sdc.az_quickstart.ranking-image';

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AZRankingComponentBuilder $componentBuilder,
    protected UuidInterface $uuid,
  ) {}

  /**
   * Builds the component subtree for an az_rankings paragraph.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The az_rankings paragraph.
   *
   * @return array{tree: array<int, array<string, mixed>>, warnings: string[]}
   *   Canvas component tree items - the deck first, then its children - and
   *   human-readable notes about settings that could not be converted.
   */
  public function buildRankings(ParagraphInterface $paragraph): array {
    if ($paragraph->bundle() !== 'az_rankings') {
      throw new \InvalidArgumentException(sprintf('Paragraph %d is not a rankings paragraph.', $paragraph->id()));
    }
    $warnings = [];
    $tree = $this->convertRankingsParagraph($paragraph, $warnings);
    return ['tree' => $tree, 'warnings' => $warnings];
  }

  /**
   * Converts an az_rankings paragraph into ranking-deck component tree items.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The az_rankings paragraph.
   * @param string[] $warnings
   *   Collects notes about settings that have no Canvas equivalent.
   *
   * @return array<int, array<string, mixed>>
   *   Component tree items: the deck first, then its children.
   */
  protected function convertRankingsParagraph(ParagraphInterface $paragraph, array &$warnings): array {
    $items = $paragraph->get('field_az_rankings');
    if ($items->isEmpty()) {
      return [];
    }

    if ($paragraph->hasField('field_az_title') && !$paragraph->get('field_az_title')->isEmpty()) {
      $warnings[] = (string) $this->t('The rankings title "@title" was not converted; add a heading in Canvas if needed.', [
        '@title' => trim(strip_tags((string) $paragraph->get('field_az_title')->value)),
      ]);
    }

    $ranking_defaults = $paragraph->getAllBehaviorSettings()['az_rankings_paragraph_behavior'] ?? [];
    $deck_props = $this->componentBuilder->buildDeckProps($ranking_defaults);

    // Mirrors AZRankingDefaultFormatter's row gap plus the paragraph
    // wrapper's bottom spacing, which Canvas has no wrapper for.
    $deck_inputs = $deck_props;
    $deck_inputs['utility_classes'] = array_values(array_filter([
      'pb-4',
      $ranking_defaults['az_display_settings']['bottom_spacing'] ?? '',
    ]));

    $deck_uuid = $this->uuid->generate();
    $tree = [
      [
        'uuid' => $deck_uuid,
        'component_id' => self::DECK_COMPONENT,
        'inputs' => $deck_inputs,
      ],
    ];

    foreach ($items as $item) {
      assert($item instanceof AZRankingItem);
      $values = $this->componentBuilder->extractItemValues($item);
      if (($values['options']['ranking_type'] ?? 'standard') === 'image_only') {
        $component_id = self::IMAGE_COMPONENT;
        $inputs = $this->buildImageInputs($values, $deck_props);
      }
      else {
        $component_id = self::RANKING_COMPONENT;
        $inputs = $this->buildRankingInputs($values, $ranking_defaults);
      }
      $tree[] = [
        'uuid' => $this->uuid->generate(),
        'component_id' => $component_id,
        'parent_uuid' => $deck_uuid,
        'slot' => 'rankings',
        'inputs' => $inputs,
      ];
    }

    return $tree;
  }

  /**
   * Builds Canvas inputs for an az_quickstart:ranking component.
   */
  protected function buildRankingInputs(array $values, array $ranking_defaults): array {
    $inputs = $this->componentBuilder->buildRankingComponent($values, $ranking_defaults)['#props'];
    // Canvas stores uri-reference props as link field values, not the
    // resolved URL string the render array carries.
    if (array_key_exists('link_url', $inputs)) {
      $uri = $this->toLinkUri($values['link_uri'] ?? '');
      if ($uri === NULL) {
        unset($inputs['link_url']);
      }
      else {
        $inputs['link_url'] = ['uri' => $uri, 'options' => []];
      }
    }
    return $this->removeEmptyInputs($inputs);
  }

  /**
   * Builds Canvas inputs for an az_quickstart:ranking-image component.
   */
  protected function buildImageInputs(array $values, array $deck_props): array {
    $inputs = $this->componentBuilder->buildImageComponent($values, $deck_props)['#props'];
    // Canvas stores media-backed image props as a media reference, which is
    // what gives editors the media library picker for the image.
    unset($inputs['image']);
    if (!empty($values['media']) && $this->entityTypeManager->getStorage('media')->load($values['media'])) {
      $inputs['image'] = ['target_id' => (string) $values['media']];
    }
    return $this->removeEmptyInputs($inputs);
  }

  /**
   * Converts a stored ranking link value into a link field URI.
   *
   * Follows the same rules as core's link widget for user-entered strings.
   *
   * @see \Drupal\link\Plugin\Field\FieldWidget\LinkWidget::getUserEnteredStringAsUri()
   */
  protected function toLinkUri(string $link): ?string {
    $link = trim($link);
    if ($link === '') {
      return NULL;
    }
    if ($link === '<front>') {
      return 'internal:/';
    }
    // A bare '#' is a placeholder that is not a valid uri-reference, matching
    // AZRankingComponentBuilder::resolveLinkUrl().
    if ($link === '#') {
      return 'internal:#top';
    }
    if (parse_url($link, PHP_URL_SCHEME) !== NULL) {
      return $link;
    }
    if (!in_array($link[0], ['/', '?', '#'], TRUE)) {
      $link = '/' . $link;
    }
    return 'internal:' . $link;
  }

  /**
   * Drops empty optional inputs so Canvas falls back to component defaults.
   */
  protected function removeEmptyInputs(array $inputs): array {
    return array_filter($inputs, static fn ($value) => $value !== '' && $value !== NULL && $value !== []);
  }

}
