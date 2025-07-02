<?php

namespace Drupal\Tests\schema_book\Functional;

use Drupal\Tests\schema_metatag\Functional\SchemaMetatagTagsTestBase;

/**
 * Tests that each of the Schema Metatag Articles tags work correctly.
 *
 * @group schema_metatag
 * @group schema_book
 */
class SchemaBookTest extends SchemaMetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schema_metatag_test', 'schema_book'];

  /**
   * {@inheritdoc}
   */
  public $moduleName = 'schema_book';

  /**
   * {@inheritdoc}
   */
  public $groupName = 'schema_book';

}
