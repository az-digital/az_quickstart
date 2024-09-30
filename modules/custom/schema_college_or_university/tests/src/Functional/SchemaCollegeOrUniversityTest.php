<?php

namespace Drupal\Tests\schema_college_or_university\Functional;

use Drupal\Tests\schema_metatag\Functional\SchemaMetatagTagsTestBase;

/**
 * Tests that each of the Schema Metatag Articles tags work correctly.
 *
 * @group schema_metatag
 * @group schema_college_or_university
 */
class SchemaCollegeOrUniversityTest extends SchemaMetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schema_college_or_university'];

  /**
   * {@inheritdoc}
   */
  public $moduleName = 'schema_college_or_university';

  /**
   * {@inheritdoc}
   */
  public $groupName = 'schema_college_or_university';

}
