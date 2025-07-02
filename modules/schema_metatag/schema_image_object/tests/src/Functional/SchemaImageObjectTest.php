<?php

namespace Drupal\Tests\schema_image_object\Functional;

use Drupal\Tests\schema_metatag\Functional\SchemaMetatagTagsTestBase;

/**
 * Tests that each of the Schema Metatag Articles tags work correctly.
 *
 * @group schema_metatag
 * @group schema_image_object
 */
class SchemaImageObjectTest extends SchemaMetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schema_metatag_test', 'schema_image_object'];

  /**
   * {@inheritdoc}
   */
  public $moduleName = 'schema_image_object';

  /**
   * {@inheritdoc}
   */
  public $groupName = 'schema_image_object';

}
