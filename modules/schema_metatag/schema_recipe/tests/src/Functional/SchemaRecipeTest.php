<?php

namespace Drupal\Tests\schema_recipe\Functional;

use Drupal\Tests\schema_metatag\Functional\SchemaMetatagTagsTestBase;

/**
 * Tests that each of the Schema Metatag Articles tags work correctly.
 *
 * @group schema_metatag
 * @group schema_recipe
 */
class SchemaRecipeTest extends SchemaMetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schema_metatag_test', 'schema_recipe'];

  /**
   * {@inheritdoc}
   */
  public $moduleName = 'schema_recipe';

  /**
   * {@inheritdoc}
   */
  public $groupName = 'schema_recipe';

}
