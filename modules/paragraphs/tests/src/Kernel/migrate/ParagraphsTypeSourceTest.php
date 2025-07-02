<?php

namespace Drupal\Tests\paragraphs\Kernel\migrate;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;
use Drupal\Tests\paragraphs\Traits\ParagraphsSourceData;

/**
 * Test the paragraphs_type source plugin.
 *
 * @covers \Drupal\paragraphs\Plugin\migrate\source\d7\ParagraphsType
 * @group paragraphs
 */
class ParagraphsTypeSourceTest extends MigrateSqlSourceTestBase {
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
        'bundle' => 'paragraphs_field',
        'name' => 'Paragraphs Field',
        'locked' => '1',
        'description' => '',
      ],
    ];
    return $data;
  }

}
