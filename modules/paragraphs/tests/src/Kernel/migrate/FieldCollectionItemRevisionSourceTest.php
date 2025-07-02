<?php

namespace Drupal\Tests\paragraphs\Kernel\migrate;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;
use Drupal\Tests\paragraphs\Traits\FieldCollectionSourceData;

/**
 * Test the field_collection_item_revision source plugin.
 *
 * @covers \Drupal\paragraphs\Plugin\migrate\source\d7\FieldCollectionItemRevision
 * @group paragraphs
 */
class FieldCollectionItemRevisionSourceTest extends MigrateSqlSourceTestBase {
  use FieldCollectionSourceData;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate_drupal', 'paragraphs'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
    $data = static::getSourceData();
    $data[0]['expected_data'] = [
      [
        'item_id' => '2',
        'revision_id' => '2',
        'field_name' => 'field_field_collection_field',
        'bundle' => 'field_collection_field',
        'archived' => '0',
        'field_text' => [
          0 => [
            'value' => 'FCID2R2 text',
          ],
        ],
      ],
    ];
    return $data;
  }

}
