<?php

namespace Drupal\Tests\schema_special_announcement\Functional;

use Drupal\Tests\schema_metatag\Functional\SchemaMetatagTagsTestBase;

/**
 * Tests that the Schema Metatag SpecialAnnouncement tags work correctly.
 *
 * @group schema_metatag
 * @group schema_special_announcement
 */
class SchemaSpecialAnnouncementTest extends SchemaMetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schema_metatag_test', 'schema_special_announcement'];

  /**
   * {@inheritdoc}
   */
  public $moduleName = 'schema_special_announcement';

  /**
   * {@inheritdoc}
   */
  public $groupName = 'schema_special_announcement';

}
