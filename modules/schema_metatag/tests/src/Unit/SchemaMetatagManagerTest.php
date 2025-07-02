<?php

namespace Drupal\Tests\schema_metatag\Unit;

use Drupal\schema_metatag\SchemaMetatagManager;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\schema_metatag\SchemaMetatagManager
 *
 * @group schema_metatag
 * @group schema_metatag_base
 */
class SchemaMetatagManagerTest extends UnitTestCase {

  /**
   * @covers ::pivot
   * @dataProvider pivotData
   */
  public function testPivot($original, $desired) {
    $processed = SchemaMetatagManager::pivot($original);
    $this->assertEquals($desired, $processed);
  }

  /**
   * @covers ::explode
   * @dataProvider stringData
   */
  public function testExplode($original, $desired) {
    $processed = SchemaMetatagManager::explode($original);
    $this->assertEquals($desired, $processed);
  }

  /**
   * @covers ::explode
   * @dataProvider stringDataCustomSeparator
   */
  public function testExplodeWithCustomSeparator($separator, $original, $desired) {
    $processed = SchemaMetatagManager::explode($original, $separator);
    $this->assertEquals($desired, $processed);
  }

  /**
   * @covers ::arrayTrim
   * @dataProvider arrayData
   */
  public function testArrayTrim($tests, $original, $original_serialized, $desired, $desired_serialized) {
    if (!in_array('arraytrim', $tests)) {
      $this->assertTrue(TRUE);
      return;
    }
    $processed = SchemaMetatagManager::arrayTrim($original);
    $this->assertEquals($desired, $processed);
  }

  /**
   * @covers ::unserialize
   * @dataProvider arrayData
   */
  public function testUnserialize($tests, $original, $original_serialized, $desired, $desired_serialized) {
    if (!in_array('unserialize', $tests)) {
      $this->assertTrue(TRUE);
      return;
    }
    $processed = SchemaMetatagManager::unserialize($original_serialized);
    $this->assertEquals($desired, $processed);
  }

  /**
   * @covers ::serialize
   * @dataProvider arrayData
   */
  public function testSerialize($tests, $original, $original_serialized, $desired, $desired_serialized) {
    if (!in_array('serialize', $tests)) {
      $this->assertTrue(TRUE);
      return;
    }
    $processed = SchemaMetatagManager::serialize($original);
    $this->assertEquals($desired_serialized, $processed);
  }

  /**
   * @covers ::recomputeSerializedLength
   *
   * @dataProvider arrayData
   */
  public function testRecomputeSerializedLength($tests, $original, $original_serialized, $desired, $desired_serialized) {
    if (!in_array('recompute', $tests)) {
      $this->assertTrue(TRUE);
      return;
    }
    $replaced = str_replace('Organization', 'ReallyBigOrganization', $original_serialized);
    $processed = SchemaMetatagManager::recomputeSerializedLength($replaced);
    $unserialized = unserialize($processed, ['allowed_classes' => FALSE]);
    $this->assertIsArray($unserialized);
    $this->assertContains('ReallyBigOrganization', $unserialized);
  }

  /**
   * @covers ::encodeJsonld
   *
   * @dataProvider jsonData
   */
  public function testEncodeJsonld($original, $desired) {
    $processed = SchemaMetatagManager::encodeJsonld($original);
    // Eliminate spacing and line breaks that don't matter.
    $processed = str_replace(["\n", '  '], "", $processed);
    $this->assertEquals($desired, $processed);
  }

  /**
   * Provides pivot data.
   *
   * @return array
   *   - name: name of the data set.
   *    - original: original data.
   *    - desired: desired result.
   */
  public function pivotData() {
    $values = [
      'Simple pivot' => [
        [
          '@type' => 'Person',
          'name' => 'George',
          'Tags' => [
            'First',
            'Second',
            'Third',
          ],
        ],
        [
          0 => ['@type' => 'Person', 'name' => 'George', 'Tags' => 'First'],
          1 => ['@type' => 'Person', 'name' => 'George', 'Tags' => 'Second'],
          2 => ['@type' => 'Person', 'name' => 'George', 'Tags' => 'Third'],
        ],
      ],
    ];
    return $values;
  }

  /**
   * Provides array data.
   *
   * @return array
   *   - name: name of the data set.
   *    - tests: array of the tests this data set applies to.
   *    - original: original data array.
   *    - original_serialized: serialized version of original data.
   *    - desired: desired result as array.
   *    - desired_serialized: desired result, serialized.
   */
  public function arrayData() {
    $values['Dirty input'] = [
      [
        'explode',
      ],
      [
        '@type' => ' Organization',
        'name' => 'test ',
        'description' => 'more text',
      ],
      'a:1:{s:5:"@type";a:1:{s:13:" Organization";a:2:{s:4:"name";s:5:"test ";s:11:"description";s:9:"more text";}}}',
      [
        '@type' => 'Organization',
        'name' => 'test',
        'description' => 'more text',
      ],
      'a:1:{s:5:"@type";a:1:{s:12:"Organization";a:2:{s:4:"name";s:4:"test";s:11:"description";s:9:"more text";}}}',
    ];
    $values['Nested array'] = [
      [
        'arraytrim',
        'serialize',
        'unserialize',
        'explode',
        'recompute',
      ],
      [
        '@type' => 'Organization',
        'memberOf' => [
          '@type' => 'Organization',
          'name' => 'test',
          'description' => 'more text',
        ],
      ],
      'a:2:{s:5:"@type";s:12:"Organization";s:8:"memberOf";a:3:{s:5:"@type";s:12:"Organization";s:4:"name";s:4:"test";s:11:"description";s:9:"more text";}}',
      [
        '@type' => 'Organization',
        'memberOf' => [
          '@type' => 'Organization',
          'name' => 'test',
          'description' => 'more text',
        ],
      ],
      'a:2:{s:5:"@type";s:12:"Organization";s:8:"memberOf";a:3:{s:5:"@type";s:12:"Organization";s:4:"name";s:4:"test";s:11:"description";s:9:"more text";}}',
    ];
    $values['Nested array 2 levels deep'] = [
      [
        'arraytrim',
        'serialize',
        'unserialize',
        'explode',
        'recompute',
      ],
      [
        '@type' => 'Organization',
        'publishedIn' => [
          '@type' => 'CreativeWork',
          'name' => 'test',
          'description' => 'more text',
          'level3' => [
            '@type' => 'Book',
            'name' => 'Book Name',
          ],
        ],
      ],
      'a:2:{s:5:"@type";s:12:"Organization";s:11:"publishedIn";a:4:{s:5:"@type";s:12:"CreativeWork";s:4:"name";s:4:"test";s:11:"description";s:9:"more text";s:6:"level3";a:2:{s:5:"@type";s:4:"Book";s:4:"name";s:9:"Book Name";}}}',
      [
        '@type' => 'Organization',
        'publishedIn' => [
          '@type' => 'CreativeWork',
          'name' => 'test',
          'description' => 'more text',
          'level3' => [
            '@type' => 'Book',
            'name' => 'Book Name',
          ],
        ],
      ],
      'a:2:{s:5:"@type";s:12:"Organization";s:11:"publishedIn";a:4:{s:5:"@type";s:12:"CreativeWork";s:4:"name";s:4:"test";s:11:"description";s:9:"more text";s:6:"level3";a:2:{s:5:"@type";s:4:"Book";s:4:"name";s:9:"Book Name";}}}',
    ];
    $values['Nested array with nested type only'] = [
      [
        'arraytrim',
        'serialize',
        'unserialize',
        'explode',
        'recompute',
      ],
      [
        '@type' => 'Organization',
        'publishedIn' => [
          '@type' => 'CreativeWork',
        ],
        'anotherThing' => [
          '@type' => 'Thing',
          'name' => 'test',
          'description' => 'more text',
          'level3' => [
            '@type' => 'Book',
            'name' => 'Book Name',
          ],
        ],
      ],
      'a:3:{s:5:"@type";s:12:"Organization";s:11:"publishedIn";a:1:{s:5:"@type";s:12:"CreativeWork";}s:12:"anotherThing";a:4:{s:5:"@type";s:5:"Thing";s:4:"name";s:4:"test";s:11:"description";s:9:"more text";s:6:"level3";a:2:{s:5:"@type";s:4:"Book";s:4:"name";s:9:"Book Name";}}}',
      [
        '@type' => 'Organization',
        'anotherThing' => [
          '@type' => 'Thing',
          'name' => 'test',
          'description' => 'more text',
          'level3' => [
            '@type' => 'Book',
            'name' => 'Book Name',
          ],
        ],
      ],
      'a:2:{s:5:"@type";s:12:"Organization";s:12:"anotherThing";a:4:{s:5:"@type";s:5:"Thing";s:4:"name";s:4:"test";s:11:"description";s:9:"more text";s:6:"level3";a:2:{s:5:"@type";s:4:"Book";s:4:"name";s:9:"Book Name";}}}',
    ];
    $values['Empty nested array'] = [
      [
        'arraytrim',
        'serialize',
        'unserialize',
        'explode',
      ],
      [
        'name' => [],
        'Organization' => [
          '@type' => '',
          'name' => '',
        ],
      ],
      'a:2:{s:4:"name";a:0:{}s:12:"Organization";a:2:{s:5:"@type";s:0:"";s:4:"name";s:0:"";}}',
      [],
      '',
    ];
    $values['Missing type to empty array'] = [
      [
        'arraytrim',
        'serialize',
        'unserialize',
        'explode',
      ],
      [
        'organization' => [
          '@type' => '',
          'name' => 'test',
          'description' => 'test2',
        ],
      ],
      'a:1:{s:12:"organization";a:3:{s:5:"@type";s:0:"";s:4:"name";s:4:"test";s:11:"description";s:5:"test2";}}',
      [],
      '',
    ];
    $values['Type only to empty array'] = [
      [
        'arraytrim',
        'serialize',
        'unserialize',
        'explode',
      ],
      [
        'organization' => [
          '@type' => 'Organization',
          'name' => '',
          'description' => '',
        ],
      ],
      'a:1:{s:12:"organization";a:3:{s:5:"@type";s:12:"Organization";s:4:"name";s:0:"";s:11:"description";s:0:"";}}',
      [],
      '',
    ];
    $values['Array with empty parts'] = [
      ['recompute'],
      [
        '@type' => 'Organization',
        'memberOf' => [
          '@type' => 'Organization',
          'name' => '',
          'description' => 'more text',
        ],
      ],
      'a:2:{s:5:"@type";s:12:"Organization";s:8:"memberOf";a:3:{s:5:"@type";s:12:"Organization";s:4:"name";s:0:"";s:11:"description";s:9:"more text";}}',
      [
        '@type' => 'Organization',
        'memberOf' => [
          '@type' => 'Organization',
          'description' => 'more text',
        ],
      ],
      'a:2:{s:5:"@type";s:12:"Organization";s:8:"memberOf";a:3:{s:5:"@type";s:12:"Organization";s:11:"description";s:9:"more text";}}',
    ];
    return $values;
  }

  /**
   * Provides string data.
   *
   * @return array
   *   - name: name of the data set.
   *    - original: original data.
   *    - desired: desired result.
   */
  public function stringData() {
    $values = [
      'Comma separated' => [
        'First,Second,Third',
        ['First', 'Second', 'Third'],
      ],
      'Needs trimming' => [
        ' First, Second , Third',
        ['First', 'Second', 'Third'],
      ],
    ];
    return $values;
  }

  /**
   * Provides string data for explode with custom separator.
   *
   * @return array
   *   - name: name of the data set.
   *    - separator: the separator string.
   *    - original: original data.
   *    - desired: desired result.
   */
  public function stringDataCustomSeparator() {
    $values = [
      'Comma separated' => [
        ',',
        'First,Second,Third',
        ['First', 'Second', 'Third'],
      ],
      'Pipe character' => [
        '|',
        'First|Second|Third',
        ['First', 'Second', 'Third'],
      ],
      'Multiple characters' => [
        'Custom.Separator',
        'FirstCustom.SeparatorSecondCustom.SeparatorThird',
        ['First', 'Second', 'Third'],
      ],
    ];
    return $values;
  }

  /**
   * Provides json data.
   *
   * @return array
   *   - name: name of the data set.
   *    - original: original data.
   *    - desired: desired result.
   */
  public function jsonData() {
    $values = [
      'Encode simple json' => [
        [
          "@type" => "Article",
          "description" => "Curabitur arcu erat, accumsan id imperdiet et, porttitor at sem. Donec sollicitudin molestie malesuada. Donec sollicitudin molestie malesuada. Donec rutrum congue leo eget malesuada. Nulla quis lorem ut libero malesuada feugiat.",
        ],
        '{"@type": "Article","description": "Curabitur arcu erat, accumsan id imperdiet et, porttitor at sem. Donec sollicitudin molestie malesuada. Donec sollicitudin molestie malesuada. Donec rutrum congue leo eget malesuada. Nulla quis lorem ut libero malesuada feugiat."}',
      ],
      'Encode json with unicode' => [
        [
          "@type" => "Article",
          "description" => "База данни грешка.",
        ],
        '{"@type": "Article","description": "База данни грешка."}',
      ],
    ];
    return $values;
  }

}
