<?php

namespace Drupal\Tests\schema_college_or_university\Functional;

use Drupal\Tests\schema_metatag\Functional\SchemaMetatagTagsTestBase;

/**
 * Tests that each of the Schema Metatag tags work correctly.
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
  public $schemaTagsNamespace = '\\Drupal\\schema_college_or_university\\Plugin\\metatag\\Tag\\';

  /**
   * {@inheritdoc}
   */
  public $schemaTags = [
    'schema_college_or_university_name' => 'SchemaCollegeOrUniversityName',
    'schema_college_or_university_description' => 'SchemaCollegeOrUniversityDescription',
    'schema_college_or_university_url' => 'SchemaCollegeOrUniversityUrl',
  ];

}