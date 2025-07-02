<?php

namespace Drupal\Tests\schema_course\Functional;

use Drupal\Tests\schema_metatag\Functional\SchemaMetatagTagsTestBase;

/**
 * Tests that each of the Schema Metatag Articles tags work correctly.
 *
 * @group schema_metatag
 * @group schema_course
 */
class SchemaCourseTest extends SchemaMetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schema_metatag_test', 'schema_course'];

  /**
   * {@inheritdoc}
   */
  public $moduleName = 'schema_course';

  /**
   * {@inheritdoc}
   */
  public $groupName = 'schema_course';

}
