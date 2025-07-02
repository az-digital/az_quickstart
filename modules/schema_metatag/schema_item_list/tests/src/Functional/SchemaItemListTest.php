<?php

namespace Drupal\Tests\schema_item_list\Functional;

use Drupal\Tests\schema_metatag\Functional\SchemaMetatagTagsTestBase;

/**
 * Tests that each of the Schema Metatag Articles tags work correctly.
 *
 * @group schema_metatag
 * @group schema_item_list
 */
class SchemaItemListTest extends SchemaMetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schema_metatag_test', 'schema_item_list'];

  /**
   * {@inheritdoc}
   */
  public $moduleName = 'schema_item_list';

  /**
   * {@inheritdoc}
   */
  public $groupName = 'schema_item_list';

}
