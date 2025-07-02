<?php

namespace Drupal\Tests\schema_how_to\Functional;

use Drupal\Tests\schema_metatag\Functional\SchemaMetatagTagsTestBase;

/**
 * Tests that each of the Schema Metatag HowTo tags work correctly.
 *
 * @group schema_metatag
 * @group schema_how_to
 */
class SchemaHowToTest extends SchemaMetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schema_metatag_test', 'schema_how_to'];

  /**
   * {@inheritdoc}
   */
  public $moduleName = 'schema_how_to';

  /**
   * {@inheritdoc}
   */
  public $groupName = 'schema_how_to';

}
