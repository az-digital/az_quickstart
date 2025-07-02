<?php

namespace Drupal\Tests\schema_organization\Functional;

use Drupal\Tests\schema_metatag\Functional\SchemaMetatagTagsTestBase;

/**
 * Tests that each of the Schema Metatag Articles tags work correctly.
 *
 * @group schema_metatag
 * @group schema_organization
 */
class SchemaOrganizationTest extends SchemaMetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schema_metatag_test', 'schema_organization'];

  /**
   * {@inheritdoc}
   */
  public $moduleName = 'schema_organization';

  /**
   * {@inheritdoc}
   */
  public $groupName = 'schema_organization';

}
