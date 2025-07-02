<?php

namespace Drupal\Tests\metatag\Kernel\Plugin\migrate\source\d7;

use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests Metatag-D7 field instance source plugin.
 *
 * @covers \Drupal\metatag\Plugin\migrate\source\d7\MetatagFieldInstance
 *
 * @group metatag
 */
class MetatagFieldInstanceTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // Core modules.
    'field',
    'migrate_drupal',
    'node',
    'system',
    'taxonomy',
    'text',
    'user',

    // Contrib modules.
    'token',

    // This module.
    'metatag',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('user');
    $this->installConfig(static::$modules);

    $node_types = [
      'first_content_type' => 'first_content_type',
      'second_content_type' => 'second_content_type',
    ];
    foreach ($node_types as $node_type) {
      $node_type = NodeType::create([
        'type' => $node_type,
        'name' => $node_type,
      ]);
      $node_type->save();
    }
    // @code
    //    ['taxonomy_term', ['test_vocabulary' => 'test_vocabulary']],
    //    Vocabulary::create(['name' => 'test_vocabulary']);
    // @endcode
    // Setup vocabulary.
    Vocabulary::create([
      'vid' => 'test_vocabulary',
      'name' => 'test_vocabulary',
    ])->save();

    // Create a term and a comment.
    Term::create([
      'vid' => 'test_vocabulary',
      'name' => 'term',
    ])->save();

  }

  /**
   * {@inheritdoc}
   */
  public static function providerSource(): array {
    $tests[0]['source_data']['metatag'] = [
      [
        'entity_type' => 'node',
      ],
      [
        'entity_type' => 'taxonomy_term',
      ],
      [
        'entity_type' => 'user',
      ],
    ];

    $tests[0]['expected_data'] = [
      [
        'entity_type' => 'node',
        'bundle' => 'first_content_type',
      ],
      [
        'entity_type' => 'node',
        'bundle' => 'second_content_type',
      ],
      [
        'entity_type' => 'taxonomy_term',
        'bundle' => 'test_vocabulary',
      ],
      [
        'entity_type' => 'user',
        'bundle' => 'user',
      ],
    ];

    return $tests;
  }

}
