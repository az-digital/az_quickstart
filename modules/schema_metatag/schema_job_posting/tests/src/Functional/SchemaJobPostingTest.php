<?php

namespace Drupal\Tests\schema_job_posting\Functional;

use Drupal\Tests\schema_metatag\Functional\SchemaMetatagTagsTestBase;

/**
 * Tests that each of the Schema Metatag Articles tags work correctly.
 *
 * @group schema_metatag
 * @group schema_job_posting
 */
class SchemaJobPostingTest extends SchemaMetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schema_metatag_test', 'schema_job_posting'];

  /**
   * {@inheritdoc}
   */
  public $moduleName = 'schema_job_posting';

  /**
   * {@inheritdoc}
   */
  public $groupName = 'schema_job_posting';

}
