<?php

namespace Drupal\schema_metatag_test\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * A metatag tag for testing.
 *
 * @MetatagTag(
 *   id = "schema_metatag_test_postal_address",
 *   label = @Translation("Schema Metatag Test PostalAddress"),
 *   name = "postalAddress",
 *   description = @Translation("Test PostalAddress"),
 *   group = "schema_metatag_test_group",
 *   weight = 0,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "postal_address",
 *   tree_parent = {
 *     "PostalAddress",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaMetatagTestPostalAddress extends SchemaNameBase {

}
