<?php

namespace Drupal\Tests\schema_product\Functional;

use Drupal\Tests\schema_metatag\Functional\SchemaMetatagTagsTestBase;

/**
 * Tests that each of the Schema Metatag Articles tags work correctly.
 *
 * @group schema_metatag
 * @group schema_product
 */
class SchemaProductTest extends SchemaMetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schema_metatag_test', 'schema_product'];

  /**
   * {@inheritdoc}
   */
  public $moduleName = 'schema_product';

  /**
   * {@inheritdoc}
   */
  public $groupName = 'schema_product';

}
