<?php

namespace Drupal\schema_metatag_test\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * A metatag tag for testing.
 *
 * @MetatagTag(
 *   id = "schema_metatag_test_offer",
 *   label = @Translation("Schema Metatag Test Offer"),
 *   name = "offer",
 *   description = @Translation("Test element"),
 *   group = "schema_metatag_test_group",
 *   weight = 0,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "offer",
 *   tree_parent = {
 *     "Offer",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaMetatagTestOffer extends SchemaNameBase {

}
