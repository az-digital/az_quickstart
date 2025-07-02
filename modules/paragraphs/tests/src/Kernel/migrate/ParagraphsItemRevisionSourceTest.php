<?php

namespace Drupal\Tests\paragraphs\Kernel\migrate;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;
use Drupal\Tests\paragraphs\Traits\ParagraphsSourceData;

/**
 * Test the paragraphs_item_revision source plugin.
 *
 * @covers \Drupal\paragraphs\Plugin\migrate\source\d7\ParagraphsItemRevision
 * @group paragraphs
 */
class ParagraphsItemRevisionSourceTest extends MigrateSqlSourceTestBase {
  use ParagraphsSourceData;

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
        'field_name' => 'field_paragraphs_field',
        'bundle' => 'paragraphs_field',
        'archived' => '0',
        'parent_id' => '42',
        'parent_type' => 'taxonomy_term',
        'field_text' => [
          0 => [
            'value' => 'PID2R2 text',
          ],
        ],
      ],
    ];
    return $data;
  }

}
