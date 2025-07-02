<?php

namespace Drupal\schema_metatag_test\Plugin\metatag\Group;

use Drupal\schema_metatag\Plugin\metatag\Group\SchemaGroupBase;

/**
 * Provides a plugin for the 'Test Group' meta tag group.
 *
 * @MetatagGroup(
 *   id = "schema_metatag_test_group",
 *   label = @Translation("Schema.org Test group"),
 *   description = @Translation("Test element"),
 *   weight = 10,
 * )
 */
class SchemaMetatagTestGroup extends SchemaGroupBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
