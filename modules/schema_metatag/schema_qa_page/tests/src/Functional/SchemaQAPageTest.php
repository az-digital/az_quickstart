<?php

namespace Drupal\Tests\schema_qa_page\Functional;

use Drupal\Tests\schema_metatag\Functional\SchemaMetatagTagsTestBase;

/**
 * Tests that each of the Schema Metatag QA Page tags work correctly.
 *
 * @group schema_metatag
 * @group schema_qa_page
 */
class SchemaQAPageTest extends SchemaMetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schema_metatag_test', 'schema_qa_page'];

  /**
   * {@inheritdoc}
   */
  public $moduleName = 'schema_qa_page';

  /**
   * {@inheritdoc}
   */
  public $groupName = 'schema_qa_page';

}
