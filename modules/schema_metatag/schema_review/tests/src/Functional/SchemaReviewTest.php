<?php

namespace Drupal\Tests\schema_review\Functional;

use Drupal\Tests\schema_metatag\Functional\SchemaMetatagTagsTestBase;

/**
 * Tests that each of the Schema Metatag Review tags work correctly.
 *
 * @group schema_metatag
 * @group schema_review
 */
class SchemaReviewTest extends SchemaMetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schema_metatag_test', 'schema_review'];

  /**
   * {@inheritdoc}
   */
  public $moduleName = 'schema_review';

  /**
   * {@inheritdoc}
   */
  public $groupName = 'schema_review';

}
