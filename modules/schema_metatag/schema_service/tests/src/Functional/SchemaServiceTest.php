<?php

namespace Drupal\Tests\schema_service\Functional;

use Drupal\Tests\schema_metatag\Functional\SchemaMetatagTagsTestBase;

/**
 * Tests that each of the Schema Metatag Articles tags work correctly.
 *
 * @group schema_metatag
 * @group schema_service
 */
class SchemaServiceTest extends SchemaMetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schema_metatag_test', 'schema_service'];

  /**
   * {@inheritdoc}
   */
  public $moduleName = 'schema_service';

  /**
   * {@inheritdoc}
   */
  public $groupName = 'schema_service';

}
