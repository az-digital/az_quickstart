<?php

namespace Drupal\Tests\schema_movie\Functional;

use Drupal\Tests\schema_metatag\Functional\SchemaMetatagTagsTestBase;

/**
 * Tests that each of the Schema Metatag Movie tags work correctly.
 *
 * @group schema_metatag
 * @group schema_movie
 */
class SchemaMovieTest extends SchemaMetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schema_metatag_test', 'schema_movie'];

  /**
   * {@inheritdoc}
   */
  public $moduleName = 'schema_movie';

  /**
   * {@inheritdoc}
   */
  public $groupName = 'schema_movie';

}
