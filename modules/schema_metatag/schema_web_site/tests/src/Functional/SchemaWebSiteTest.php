<?php

namespace Drupal\Tests\schema_web_site\Functional;

use Drupal\Tests\schema_metatag\Functional\SchemaMetatagTagsTestBase;

/**
 * Tests that each of the Schema Metatag Articles tags work correctly.
 *
 * @group schema_metatag
 * @group schema_web_site
 */
class SchemaWebSiteTest extends SchemaMetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schema_metatag_test', 'schema_web_site'];

  /**
   * {@inheritdoc}
   */
  public $moduleName = 'schema_web_site';

  /**
   * {@inheritdoc}
   */
  public $groupName = 'schema_web_site';

}
