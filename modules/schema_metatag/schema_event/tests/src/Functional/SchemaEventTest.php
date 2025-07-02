<?php

namespace Drupal\Tests\schema_event\Functional;

use Drupal\Tests\schema_metatag\Functional\SchemaMetatagTagsTestBase;

/**
 * Tests that each of the Schema Metatag Articles tags work correctly.
 *
 * @group schema_metatag
 * @group schema_event
 */
class SchemaEventTest extends SchemaMetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schema_metatag_test', 'schema_event'];

  /**
   * {@inheritdoc}
   */
  public $moduleName = 'schema_event';

  /**
   * {@inheritdoc}
   */
  public $groupName = 'schema_event';

}
