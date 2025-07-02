<?php

namespace Drupal\Tests\schema_metatag\Functional;

/**
 * Tests that each of the SchemaMetatagTest Metatag base tags work correctly.
 *
 * @group schema_metatag
 * @group schema_metatag_base
 */
class SchemaMetatagTagsTest extends SchemaMetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // This module.
    'schema_metatag_test',

    // Required to test the list element.
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  public $moduleName = 'schema_metatag_test';

  /**
   * {@inheritdoc}
   */
  public $groupName = 'schema_metatag_test_group';

}
