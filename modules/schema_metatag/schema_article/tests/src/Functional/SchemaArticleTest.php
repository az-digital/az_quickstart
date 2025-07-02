<?php

namespace Drupal\Tests\schema_article\Functional;

use Drupal\Tests\schema_metatag\Functional\SchemaMetatagTagsTestBase;

/**
 * Tests that each of the Schema Metatag Articles tags work correctly.
 *
 * @group schema_metatag
 * @group schema_article
 */
class SchemaArticleTest extends SchemaMetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schema_metatag_test', 'schema_article'];

  /**
   * {@inheritdoc}
   */
  public $moduleName = 'schema_article';

  /**
   * {@inheritdoc}
   */
  public $groupName = 'schema_article';

}
