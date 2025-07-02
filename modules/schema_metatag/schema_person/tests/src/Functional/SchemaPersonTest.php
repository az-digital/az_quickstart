<?php

namespace Drupal\Tests\schema_person\Functional;

use Drupal\Tests\schema_metatag\Functional\SchemaMetatagTagsTestBase;

/**
 * Tests that each of the Schema Metatag Articles tags work correctly.
 *
 * @group schema_metatag
 * @group schema_person
 */
class SchemaPersonTest extends SchemaMetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schema_metatag_test', 'schema_person'];

  /**
   * {@inheritdoc}
   */
  public $moduleName = 'schema_person';

  /**
   * {@inheritdoc}
   */
  public $groupName = 'schema_person';

}
