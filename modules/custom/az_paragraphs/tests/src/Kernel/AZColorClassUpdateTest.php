<?php

namespace Drupal\Tests\az_paragraphs\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the update that renames Arizona Bootstrap 5.3 brand color classes.
 *
 * @see az_paragraphs_update_1130701()
 */
#[Group('az_paragraphs')]
#[RunTestsInSeparateProcesses]
class AZColorClassUpdateTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'az_card',
    'az_paragraphs',
    'az_ranking',
    'entity_reference_revisions',
    'field',
    'file',
    'filter',
    'image',
    'language',
    'media',
    'paragraphs',
    'system',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('paragraph');
    $this->installConfig(['language']);
    ConfigurableLanguage::createFromLangcode('es')->save();

    $this->createParagraphsType('az_text_background', ['az_text_background_paragraph_behavior']);
    $this->createParagraphsType('az_splitscreen', ['az_split_screen']);
    $this->createParagraphsType('az_link_group', ['az_link_group']);
    $this->createParagraphsType('az_cards', ['az_cards_paragraph_behavior']);
    $this->createParagraphsType('az_rankings', ['az_rankings_paragraph_behavior']);
    // A custom paragraph type with a card field but no Quickstart behaviors.
    $this->createParagraphsType('custom_cards', []);
    $this->createParagraphsType('az_text', []);

    $this->createField('az_cards', 'field_az_cards', 'az_card');
    $this->createField('custom_cards', 'field_az_cards', 'az_card');
    $this->createField('az_rankings', 'field_az_rankings', 'az_ranking');

    $this->container->get('module_handler')->loadInclude('az_paragraphs', 'install');
  }

  /**
   * Tests that color classes in behavior settings are renamed.
   */
  public function testBehaviorSettings() {
    $text_background = $this->createParagraph('az_text_background', [
      'az_text_background_paragraph_behavior' => [
        'text_background_color' => 'text-bg-midnight',
        'text_background_pattern' => 'bg-monsoon-sky-start',
        'text_background_padding' => 'py-5',
      ],
    ]);
    $splitscreen = $this->createParagraph('az_splitscreen', [
      'az_split_screen' => ['bg_color' => 'text-bg-warm-gray'],
    ]);
    $link_group = $this->createParagraph('az_link_group', [
      'az_link_group' => ['button_color' => 'btn-outline-sky'],
    ]);
    $cards = $this->createParagraph('az_cards', [
      'az_cards_paragraph_behavior' => ['card_deck_title_color' => 'text-oasis'],
    ]);
    $rankings = $this->createParagraph('az_rankings', [
      'az_rankings_paragraph_behavior' => ['ranking_deck_title_color' => 'text-oasis'],
    ]);
    $text = $this->createParagraph('az_text', [
      'az_text_background_paragraph_behavior' => ['text_background_color' => 'text-bg-chili'],
    ]);
    $revision_id = $text_background->getRevisionId();

    $this->runUpdate();

    $settings = $this->reload($text_background)->getAllBehaviorSettings();
    $this->assertSame('text-bg-tinta', $settings['az_text_background_paragraph_behavior']['text_background_color']);
    $this->assertSame('bg-monsoon-rain-start', $settings['az_text_background_paragraph_behavior']['text_background_pattern']);
    $this->assertSame('py-5', $settings['az_text_background_paragraph_behavior']['text_background_padding']);
    $this->assertSame('text-bg-caliche', $this->reload($splitscreen)->getAllBehaviorSettings()['az_split_screen']['bg_color']);
    $this->assertSame('btn-outline-rain', $this->reload($link_group)->getAllBehaviorSettings()['az_link_group']['button_color']);
    $this->assertSame('text-arroyo-blue', $this->reload($cards)->getAllBehaviorSettings()['az_cards_paragraph_behavior']['card_deck_title_color']);
    $this->assertSame('text-arroyo-blue', $this->reload($rankings)->getAllBehaviorSettings()['az_rankings_paragraph_behavior']['ranking_deck_title_color']);
    // Paragraph types without these behaviors or fields are not updated.
    $this->assertSame('text-bg-chili', $this->reload($text)->getAllBehaviorSettings()['az_text_background_paragraph_behavior']['text_background_color']);
    // The current revision is updated, rather than a new revision created.
    $this->assertEquals($revision_id, $this->reload($text_background)->getRevisionId());
  }

  /**
   * Tests that color classes in card and ranking fields are renamed.
   */
  public function testFieldValues() {
    $cards = $this->createParagraph('az_cards', [], [
      'field_az_cards' => [
        [
          'title' => 'First card',
          'options' => [
            'class' => 'text-bg-chili',
            'link_style' => 'btn w-100 btn-sky',
            'title_alignment' => 'text-start',
          ],
        ],
        [
          'title' => 'Second card',
          'options' => [
            'class' => 'text-bg-blue',
            'link_style' => 'btn w-100 btn-red',
          ],
        ],
      ],
    ]);
    $custom_cards = $this->createParagraph('custom_cards', [], [
      'field_az_cards' => [
        [
          'title' => 'Custom card',
          'options' => ['class' => 'text-bg-river'],
        ],
      ],
    ]);
    $rankings = $this->createParagraph('az_rankings', [], [
      'field_az_rankings' => [
        [
          'ranking_heading' => 'First ranking',
          'ranking_font_color' => 'ranking-text-midnight',
          'ranking_link_style' => 'w-100 btn btn-outline-sky mt-2',
          'options' => [
            'class' => 'bg-cool-gray',
            'hover_class' => 'bg-oasis',
            'ranking_type' => 'standard',
          ],
        ],
      ],
    ]);

    $this->runUpdate();

    $items = $this->reload($cards)->get('field_az_cards')->getValue();
    $this->assertSame([
      'class' => 'text-bg-sonoran-red',
      'link_style' => 'btn w-100 btn-rain',
      'title_alignment' => 'text-start',
    ], $items[0]['options']);
    $this->assertSame('First card', $items[0]['title']);
    $this->assertSame([
      'class' => 'text-bg-blue',
      'link_style' => 'btn w-100 btn-red',
    ], $items[1]['options']);
    $this->assertSame('text-bg-shade', $this->reload($custom_cards)->get('field_az_cards')->getValue()[0]['options']['class']);

    $item = $this->reload($rankings)->get('field_az_rankings')->getValue()[0];
    $this->assertSame('ranking-text-tinta', $item['ranking_font_color']);
    $this->assertSame('w-100 btn btn-outline-rain mt-2', $item['ranking_link_style']);
    $this->assertSame([
      'class' => 'bg-cloud',
      'hover_class' => 'bg-arroyo-blue',
      'ranking_type' => 'standard',
    ], $item['options']);
  }

  /**
   * Tests that color classes are renamed in every translation.
   */
  public function testTranslations() {
    $cards = $this->createParagraph('az_cards', [], [
      'field_az_cards' => [
        ['title' => 'Card', 'options' => ['class' => 'text-bg-chili']],
      ],
    ]);
    $cards->addTranslation('es', [
      'field_az_cards' => [
        ['title' => 'Tarjeta', 'options' => ['class' => 'text-bg-bloom']],
      ],
    ])->save();

    $this->runUpdate();

    $cards = $this->reload($cards);
    $this->assertSame('text-bg-sonoran-red', $cards->get('field_az_cards')->getValue()[0]['options']['class']);
    $this->assertSame('text-bg-bougainvillea', $cards->getTranslation('es')->get('field_az_cards')->getValue()[0]['options']['class']);
  }

  /**
   * Tests that a missing behavior plugin does not stop the update.
   */
  public function testMissingBehaviorPlugin() {
    // Write the config directly. Saving this paragraph type through the
    // entity API would create the plugin and fail before the update runs.
    $this->config('paragraphs.paragraphs_type.custom_removed')
      ->setData([
        'langcode' => 'en',
        'status' => TRUE,
        'dependencies' => [],
        'id' => 'custom_removed',
        'label' => 'custom_removed',
        'icon_uuid' => NULL,
        'icon_default' => NULL,
        'description' => '',
        'behavior_plugins' => ['az_removed_behavior' => ['enabled' => TRUE]],
      ])
      ->save();
    $splitscreen = $this->createParagraph('az_splitscreen', [
      'az_split_screen' => ['bg_color' => 'text-bg-leaf'],
    ]);

    $this->runUpdate();

    $this->assertSame('text-bg-saguaro', $this->reload($splitscreen)->getAllBehaviorSettings()['az_split_screen']['bg_color']);
  }

  /**
   * Tests that the update processes paragraphs in batches.
   */
  public function testBatches() {
    $paragraphs = [];
    for ($i = 0; $i < 30; $i++) {
      $paragraphs[] = $this->createParagraph('az_splitscreen', [
        'az_split_screen' => ['bg_color' => 'text-bg-leaf'],
      ]);
    }

    $sandbox = [];
    az_paragraphs_update_1130701($sandbox);
    $this->assertLessThan(1, $sandbox['#finished']);
    $result = az_paragraphs_update_1130701($sandbox);
    $this->assertEquals(1, $sandbox['#finished']);
    $this->assertSame('Renamed Arizona Bootstrap color classes in 30 paragraphs.', (string) $result);

    foreach ($paragraphs as $paragraph) {
      $this->assertSame('text-bg-saguaro', $this->reload($paragraph)->getAllBehaviorSettings()['az_split_screen']['bg_color']);
    }
  }

  /**
   * Tests that the update finishes when there is nothing to update.
   */
  public function testNoParagraphs() {
    $sandbox = [];
    $result = az_paragraphs_update_1130701($sandbox);
    $this->assertEquals(1, $sandbox['#finished']);
    $this->assertSame('Renamed Arizona Bootstrap color classes in 0 paragraphs.', (string) $result);
  }

  /**
   * Runs the update until it finishes.
   */
  protected function runUpdate(): void {
    $sandbox = [];
    do {
      az_paragraphs_update_1130701($sandbox);
    } while ($sandbox['#finished'] < 1);
  }

  /**
   * Creates a paragraph type with the given behavior plugins enabled.
   *
   * @param string $id
   *   The paragraph type ID.
   * @param string[] $behavior_plugins
   *   The IDs of the behavior plugins to enable.
   */
  protected function createParagraphsType(string $id, array $behavior_plugins): void {
    $behaviors = [];
    foreach ($behavior_plugins as $plugin_id) {
      $behaviors[$plugin_id] = ['enabled' => TRUE];
    }
    ParagraphsType::create([
      'id' => $id,
      'label' => $id,
      'behavior_plugins' => $behaviors,
    ])->save();
  }

  /**
   * Adds a translatable, multi-value field to a paragraph type.
   *
   * @param string $bundle
   *   The paragraph type ID.
   * @param string $field_name
   *   The field name.
   * @param string $type
   *   The field type.
   */
  protected function createField(string $bundle, string $field_name, string $type): void {
    if (!FieldStorageConfig::loadByName('paragraph', $field_name)) {
      FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => 'paragraph',
        'type' => $type,
        'cardinality' => -1,
      ])->save();
    }
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'paragraph',
      'bundle' => $bundle,
      'translatable' => TRUE,
    ])->save();
  }

  /**
   * Creates and saves a paragraph.
   *
   * @param string $bundle
   *   The paragraph type ID.
   * @param array $behavior_settings
   *   The behavior settings, keyed by behavior plugin ID.
   * @param array $values
   *   Other field values.
   *
   * @return \Drupal\paragraphs\ParagraphInterface
   *   The saved paragraph.
   */
  protected function createParagraph(string $bundle, array $behavior_settings, array $values = []): ParagraphInterface {
    $paragraph = Paragraph::create(['type' => $bundle] + $values);
    $paragraph->setAllBehaviorSettings($behavior_settings);
    $paragraph->save();
    return $paragraph;
  }

  /**
   * Loads a fresh copy of a paragraph from storage.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph.
   *
   * @return \Drupal\paragraphs\ParagraphInterface
   *   The reloaded paragraph.
   */
  protected function reload(ParagraphInterface $paragraph): ParagraphInterface {
    $storage = $this->container->get('entity_type.manager')->getStorage('paragraph');
    $storage->resetCache([$paragraph->id()]);
    return $storage->load($paragraph->id());
  }

}
