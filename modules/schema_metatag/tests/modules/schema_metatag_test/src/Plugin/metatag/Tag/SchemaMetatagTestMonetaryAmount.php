<?php

namespace Drupal\schema_metatag_test\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * A metatag tag for testing.
 *
 * @MetatagTag(
 *   id = "schema_metatag_test_monetary_amount",
 *   label = @Translation("Schema Metatag Test MonetaryAmount"),
 *   name = "monetaryAmount",
 *   description = @Translation("Test MonetaryAmount element"),
 *   group = "schema_metatag_test_group",
 *   weight = 0,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "monetary_amount",
 *   tree_parent = {
 *     "MonetaryAmount",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaMetatagTestMonetaryAmount extends SchemaNameBase {

}
