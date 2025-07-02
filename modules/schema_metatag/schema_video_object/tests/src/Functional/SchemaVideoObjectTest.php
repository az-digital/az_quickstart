<?php

namespace Drupal\Tests\schema_video_object\Functional;

use Drupal\Tests\schema_metatag\Functional\SchemaMetatagTagsTestBase;

/**
 * Tests that each of the Schema Metatag Articles tags work correctly.
 *
 * @group schema_metatag
 * @group schema_video_object
 */
class SchemaVideoObjectTest extends SchemaMetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schema_metatag_test', 'schema_video_object'];

  /**
   * {@inheritdoc}
   */
  public $moduleName = 'schema_video_object';

  /**
   * {@inheritdoc}
   */
  public $groupName = 'schema_video_object';

}
