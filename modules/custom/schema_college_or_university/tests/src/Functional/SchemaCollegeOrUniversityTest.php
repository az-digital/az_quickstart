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
    'schema_college_or_university_image' => 'SchemaCollegeOrUniversityImage',
    'schema_college_or_university_logo' => 'SchemaCollegeOrUniversityLogo',
    'schema_college_or_university_address' => 'SchemaCollegeOrUniversityAddress',
    'schema_college_or_university_email' => 'SchemaCollegeOrUniversityEmail',
    'schema_college_or_university_telephone' => 'SchemaCollegeOrUniversityTelephone',
    'schema_college_or_university_parent_organization' => 'SchemaCollegeOrUniversityParentOrganization',
    'schema_college_or_university_same_as' => 'SchemaCollegeOrUniversitySameAs',
  ];

}