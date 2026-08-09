<?php

namespace Drupal\az_ranking\Plugin\Field\FieldFormatter;

use Drupal\az_ranking\Plugin\Field\FieldType\AZRankingItem;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'az_ranking_default' formatter.
 *
 * Renders the field through the az_quickstart:ranking,
 * az_quickstart:ranking-image and az_quickstart:ranking-deck Single
 * Directory Components, so a ranking built in the page builder and one
 * composed in Canvas come out as the same markup.
 *
 * The item-to-props mapping lives in AZRankingComponentBuilder, which
 * AZRankingWidget also uses for its live edit-form preview.
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
   * The AZRankingComponentBuilder service.
   *
   * @var \Drupal\az_ranking\AZRankingComponentBuilder
   */
  protected $componentBuilder;

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

    $instance->componentBuilder = $container->get('az_ranking.component_builder');
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
    // Render nothing when every ranking has been removed, rather than an
    // empty deck. Carrying on would hand the slot below an empty array,
    // which core rejects with a fatal.
    if ($items->isEmpty()) {
      return [];
    }

    $rankings = [];
    $interactive_links = (bool) $this->getSetting('interactive_links');

    // Build the deck's props first, before the loop. Rationale:
    // buildImageComponent() needs the deck's column count at each
    // breakpoint so it can clamp every image's width_span_* props against
    // it - see that method's docblock.
    $deck_props = [];
    $ranking_defaults = [];
    $parent = $items->getEntity();
    if ($parent instanceof ParagraphInterface) {
      $behavior_settings = $parent->getAllBehaviorSettings();
      $ranking_defaults = $behavior_settings['az_rankings_paragraph_behavior'] ?? [];
      $deck_props = $this->componentBuilder->buildDeckProps($ranking_defaults);
      // Legacy version of this flie used `pb-4` to make a gap between rows
      // of rankings. New SDC's use `gap: 1.5rem`, which doesn't apply to the
      // last row of rankings in a ranking-deck. So we're putting it here to
      // match.
      $deck_props['utility_classes'] = ['pb-4'];
    }

    foreach ($items as $item) {
      assert($item instanceof AZRankingItem);
      $values = $this->componentBuilder->extractItemValues($item);
      $ranking_type = $values['options']['ranking_type'] ?? 'standard';
      $ranking = $ranking_type === 'image_only'
        ? $this->componentBuilder->buildImageComponent($values, $deck_props)
        : $this->componentBuilder->buildRankingComponent($values, $ranking_defaults);

      // With "Interactive Links" off, stop this item's link navigating - if
      // it has one. ranking-image never sets link_url, so image_only items
      // are untouched, which matches the legacy template: it only ever put
      // this on the #type => link element.
      //
      // Kept out of ranking.component.yml on purpose. "Disable my own links
      // because a Paragraphs Preview view mode is rendering me" says nothing
      // about what a ranking card is; it belongs to one admin workflow that
      // Canvas has no equivalent of.
      if (!$interactive_links && isset($ranking['#props']['link_url'])) {
        $ranking['#attributes']['class'][] = 'az-ranking-no-follow';
        $ranking['#attached']['library'][] = 'az_ranking/az_ranking_no_follow';
      }

      $rankings[] = $ranking;
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

}
