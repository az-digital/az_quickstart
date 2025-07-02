<?php

namespace Drupal\schema_metatag_test\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * A metatag tag for testing.
 *
 * @MetatagTag(
 *   id = "schema_metatag_test_contact_point",
 *   label = @Translation("Schema Metatag Test ContactPoint"),
 *   name = "contactPoint",
 *   description = @Translation("Test element"),
 *   group = "schema_metatag_test_group",
 *   weight = 0,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "contact_point",
 *   tree_parent = {
 *     "ContactPoint",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaMetatagTestContactPoint extends SchemaNameBase {

}
