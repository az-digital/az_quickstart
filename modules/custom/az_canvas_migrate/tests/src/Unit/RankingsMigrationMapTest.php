<?php

namespace Drupal\Tests\az_canvas_migrate\Unit;

use Drupal\Component\Serialization\Yaml;
use Drupal\Tests\UnitTestCase;
use Drupal\az_ranking\AZRankingComponentBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Keeps the rankings migration's value maps in sync with the Paragraphs ones.
 *
 * The migration and AZRankingComponentBuilder render the same SDCs, so a
 * class or option added to one has to be added to the other.
 */
#[Group('az_canvas_migrate')]
class RankingsMigrationMapTest extends UnitTestCase {

  /**
   * Tests that a static_map in the migration matches a builder constant.
   *
   * @param string[] $path
   *   Keys leading to the static_map plugin configuration in the migration.
   * @param array $expected
   *   The AZRankingComponentBuilder map.
   */
  #[DataProvider('providerMaps')]
  public function testMapMatchesComponentBuilder(array $path, array $expected): void {
    $migration = Yaml::decode(file_get_contents(dirname(__DIR__, 3) . '/migrations/az_paragraph_rankings_to_canvas.yml'));
    $plugin = $migration;
    foreach ($path as $key) {
      $this->assertArrayHasKey($key, $plugin, implode(' > ', $path));
      $plugin = $plugin[$key];
    }
    $this->assertSame('static_map', $plugin['plugin']);
    $this->assertEquals($expected, $plugin['map']);
  }

  /**
   * Provides migration paths and the builder maps they should match.
   */
  public static function providerMaps(): array {
    $deck = ['process'];
    $item = ['process', 'slots/rankings', 'process'];
    return [
      'columns_desktop' => [[...$deck, 'inputs/columns_desktop'], AZRankingComponentBuilder::DESKTOP_COLUMN_MAP],
      'columns_tablet' => [[...$deck, 'inputs/columns_tablet'], AZRankingComponentBuilder::TABLET_COLUMN_MAP],
      'columns_phone' => [[...$deck, 'inputs/columns_phone'], AZRankingComponentBuilder::PHONE_COLUMN_MAP],
      'item columns_desktop' => [[...$item, '_columns_desktop'], AZRankingComponentBuilder::DESKTOP_COLUMN_MAP],
      'item columns_tablet' => [[...$item, '_columns_tablet'], AZRankingComponentBuilder::TABLET_COLUMN_MAP],
      'item columns_phone' => [[...$item, '_columns_phone'], AZRankingComponentBuilder::PHONE_COLUMN_MAP],
      'link_style' => [[...$item, 'inputs/link_style', 1], AZRankingComponentBuilder::LINK_STYLE_CLASS_MAP],
      'header_style' => [[...$item, 'inputs/header_style'], AZRankingComponentBuilder::HEADER_STYLE_MAP],
      'background' => [[...$item, 'inputs/background'], AZRankingComponentBuilder::BACKGROUND_CLASS_MAP],
      'hover_background' => [[...$item, 'inputs/hover_background'], AZRankingComponentBuilder::BACKGROUND_CLASS_MAP],
      'font_color' => [[...$item, 'inputs/font_color', 1], AZRankingComponentBuilder::FONT_COLOR_CLASS_MAP],
    ];
  }

}
