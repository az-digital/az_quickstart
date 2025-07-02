<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_plus\Unit\process;

use Drupal\Component\Utility\Html;
use Drupal\migrate_plus\Plugin\migrate\process\DomSelect;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the dom_select process plugin.
 *
 * @group migrate
 * @coversDefaultClass \Drupal\migrate_plus\Plugin\migrate\process\DomSelect
 */
final class DomSelectTest extends MigrateProcessTestCase {

  /**
   * @covers ::transform
   *
   * @dataProvider providerTestTransform
   */
  public function testTransform(string $input_string, array $configuration, array $output_array): void {
    $value = Html::load($input_string);
    $elements = (new DomSelect($configuration, 'dom_select', []))
      ->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertEquals($output_array, $elements);
  }

  /**
   * Dataprovider for testTransform().
   */
  public static function providerTestTransform(): array {
    $lists = '<ul><li>Item 1</li><li>Item 2</li><li><ul><li>Item 3.1</li><li>Item 3.2</li></ul></li><li>Item 4</li><li>Item 5</li></ul>';
    $image = '<p>A broken image: <img src="https://www.example.com/img/foo.jpg" alt="metasyntactic image" /></p>';
    $cases = [
      'any li, no limit' => [
        $lists,
        ['selector' => '//li'],
        [
          'Item 1',
          'Item 2',
          'Item 3.1Item 3.2',
          'Item 3.1',
          'Item 3.2',
          'Item 4',
          'Item 5',
        ],
      ],
      'any li, limit 3' => [
        $lists,
        ['selector' => '//li', 'limit' => 3],
        [
          'Item 1',
          'Item 2',
          'Item 3.1Item 3.2',
        ],
      ],
      'any li, limit 4' => [
        $lists,
        ['selector' => '//li', 'limit' => 4],
        // The fourth match is Item 3.1.
        [
          'Item 1',
          'Item 2',
          'Item 3.1Item 3.2',
          'Item 3.1',
        ],
      ],
      'top-level li, limit 4' => [
        $lists,
        // Both Html::load() and the dom process plugin wrap HTML snippets in
        // <html> and <body> tags.
        ['selector' => '/html/body/ul/li', 'limit' => 4],
        [
          'Item 1',
          'Item 2',
          'Item 3.1Item 3.2',
          'Item 4',
        ],
      ],
      'nested li, no limit' => [
        $lists,
        ['selector' => '//li//li'],
        [
          'Item 3.1',
          'Item 3.2',
        ],
      ],
      'nested li, limit 1' => [
        $lists,
        ['selector' => '//li//li', 'limit' => 1],
        [
          'Item 3.1',
        ],
      ],
      'image src attribute' => [
        $image,
        ['selector' => '//img/@src', 'limit' => 1],
        [
          'https://www.example.com/img/foo.jpg',
        ],
      ],
      'image src or alt attribute' => [
        $image,
        ['selector' => '//p/img/@src|/html/body/p/img/@alt'],
        [
          'https://www.example.com/img/foo.jpg',
          'metasyntactic image',
        ],
      ],
    ];

    return $cases;
  }

}
