<?php

namespace Drupal\Tests\schema_place\Functional;

use Drupal\Tests\schema_metatag\Functional\SchemaMetatagTagsTestBase;

/**
 * Tests that each of the Schema Metatag Place tags work correctly.
 *
 * @group schema_metatag
 * @group schema_place
 */
class SchemaPlaceTest extends SchemaMetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schema_metatag_test', 'schema_place'];

  /**
   * {@inheritdoc}
   */
  public $moduleName = 'schema_place';

  /**
   * {@inheritdoc}
   */
  public $groupName = 'schema_place';

}
