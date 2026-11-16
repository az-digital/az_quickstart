<?php

namespace Drupal\Tests\az_canvas_migrate\Unit;

use Drupal\Component\Serialization\Yaml;
use Drupal\Tests\UnitTestCase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\process\StrReplace;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the page migration's rewrite of node meta tag tokens.
 *
 * Runs the str_replace steps exactly as configured in
 * az_flexible_page_to_canvas_page.yml.
 */
#[Group('az_canvas_migrate')]
class MetatagTokenRewriteTest extends UnitTestCase {

  /**
   * The page migration's process configuration.
   *
   * @var array
   */
  protected array $process;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $migration = Yaml::decode(file_get_contents(dirname(__DIR__, 3) . '/migrations/az_flexible_page_to_canvas_page.yml'));
    $this->process = $migration['process'];
  }

  /**
   * Runs a str_replace step from the migration on a value.
   */
  protected function replace(array $configuration, string $value): string {
    $this->assertSame('str_replace', $configuration['plugin']);
    $plugin = new StrReplace($configuration, 'str_replace', []);
    return $plugin->transform($value, $this->createMock(MigrateExecutableInterface::class), new Row(), 'metatags');
  }

  /**
   * Tests that node tokens become their Canvas page equivalents.
   */
  public function testRewritesNodeTokens(): void {
    $source = json_encode([
      'canonical_url' => '[node:az-canonical-absolute-url]',
      'shortlink' => '[node:az-canonical-url]',
      'description' => '[node:field_az_summary:value]',
      'og_image' => '[node:field_az_media_image:entity:field_media_az_image]',
      'keywords' => '[node:field_az_page_category]',
      'title' => 'Custom [node:title] | [site:name]',
      'og_url' => '[node:url:absolute]',
    ]);
    $expected = json_encode([
      'canonical_url' => '[canvas_page:url:absolute]',
      'shortlink' => '[canvas_page:url]',
      'description' => '[canvas_page:description:value]',
      'og_image' => '[canvas_page:image:entity:field_media_az_image]',
      'keywords' => '[canvas_page:field_az_page_category]',
      'title' => 'Custom [canvas_page:title] | [site:name]',
      'og_url' => '[canvas_page:url:absolute]',
    ]);
    $this->assertSame($expected, $this->replace($this->process['metatags'][1], $source));
  }

  /**
   * Tests that the notice step lists only unconverted node tokens.
   */
  public function testNoticeListsUnconvertedTokens(): void {
    $step = $this->process['_metatags_notice'][1];
    $value = json_encode([
      'keywords' => '[node:field_az_tags], [canvas_page:title] [node:author:name]',
      'title' => '[site:name]',
    ]);
    $this->assertSame('[node:field_az_tags] [node:author:name]', trim($this->replace($step, $value)));
    $this->assertSame('', trim($this->replace($step, json_encode(['title' => '[canvas_page:title] | [site:name]']))));
  }

}
