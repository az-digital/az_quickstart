<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_plus\Unit\process;

use Drupal\Component\Utility\Html;
use Drupal\migrate_plus\Plugin\migrate\process\DomRemove;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the dom_remove process plugin.
 *
 * @group migrate
 * @coversDefaultClass \Drupal\migrate_plus\Plugin\migrate\process\DomRemove
 */
final class DomRemoveTest extends MigrateProcessTestCase {

  /**
   * @covers ::transform
   *
   * @dataProvider providerTestTransform
   */
  public function testTransform($input_string, $configuration, $output_string): void {
    $value = Html::load($input_string);
    $document = (new DomRemove($configuration, 'dom_remove', []))
      ->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertTrue($document instanceof \DOMDocument);
    $this->assertEquals($output_string, Html::serialize($document));
  }

  /**
   * Dataprovider for testTransform().
   */
  public static function providerTestTransform(): array {
    $input_string = '<ul><li>Item 1</li><li>Item 2</li><li><ul><li>Item 3.1</li><li>Item 3.2</li></ul></li><li>Item 4</li><li>Item 5</li></ul>';
    $attribute_input_string = '<div style="font-size:15px;"><a class="btn-lg" href="#" style="padding: 10px;">Button</a><p class="lead-paragraph">Testing</p></div>';
    $cases = [
      'any li, no limit' => [
        $input_string,
        ['selector' => '//li'],
        '<ul></ul>',
      ],
      'any li, limit 3' => [
        $input_string,
        ['selector' => '//li', 'limit' => 3],
        '<ul><li>Item 4</li><li>Item 5</li></ul>',
      ],
      'any li, limit 4' => [
        $input_string,
        ['selector' => '//li', 'limit' => 4],
        // The fourth match is Item 3.1.
        '<ul><li>Item 4</li><li>Item 5</li></ul>',
      ],
      'top-level li, limit 4' => [
        $input_string,
        // Both Html::load() and the dom process plugin wrap HTML snippets in
        // <html> and <body> tags.
        ['selector' => '/html/body/ul/li', 'limit' => 4],
        '<ul><li>Item 5</li></ul>',
      ],
      'nested li, no limit' => [
        $input_string,
        ['selector' => '//li//li'],
        '<ul><li>Item 1</li><li>Item 2</li><li><ul></ul></li><li>Item 4</li><li>Item 5</li></ul>',
      ],
      'nested li, limit 1' => [
        $input_string,
        ['selector' => '//li//li', 'limit' => 1],
        '<ul><li>Item 1</li><li>Item 2</li><li><ul><li>Item 3.2</li></ul></li><li>Item 4</li><li>Item 5</li></ul>',
      ],
      'specific item' => [
        $input_string,
        ['selector' => '//li[text() = "Item 3.1"]'],
        '<ul><li>Item 1</li><li>Item 2</li><li><ul><li>Item 3.2</li></ul></li><li>Item 4</li><li>Item 5</li></ul>',
      ],
      'all items with sub-lists' => [
        $input_string,
        ['selector' => '//li[./ul]'],
        '<ul><li>Item 1</li><li>Item 2</li><li>Item 4</li><li>Item 5</li></ul>',
      ],
      'attribute, no limit' => [
        $attribute_input_string,
        ['selector' => '//*[@style]', 'mode' => 'attribute', 'attribute' => 'style'],
        '<div><a class="btn-lg" href="#">Button</a><p class="lead-paragraph">Testing</p></div>',
      ],
      'attribute, limit 1' => [
        $attribute_input_string,
        ['selector' => '//*[@style]', 'mode' => 'attribute', 'attribute' => 'style', 'limit' => 1],
        '<div><a class="btn-lg" href="#" style="padding: 10px;">Button</a><p class="lead-paragraph">Testing</p></div>',
      ],
      'attribute in specific tag' => [
        $attribute_input_string,
        ['selector' => '//p[@class]', 'mode' => 'attribute', 'attribute' => 'class'],
        '<div style="font-size:15px;"><a class="btn-lg" href="#" style="padding: 10px;">Button</a><p>Testing</p></div>',
      ],
      'attribute not found' => [
        $attribute_input_string,
        ['selector' => 'p[@class]', 'mode' => 'attribute', 'attribute' => 'data-test'],
        $attribute_input_string,
      ],
    ];

    return $cases;
  }

  /**
   * Tests running remove attribute without specifying an attribute to remove.
   */
  public function testMissingConfiguration(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The "attribute" must be set if "mode" is set to "attribute".');
    (new DomRemove(['selector' => 'p', 'mode' => 'attribute'], 'dom_remove', []));
  }

}
