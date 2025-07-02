<?php

namespace Drupal\schema_metatag_test\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * A metatag tag for testing.
 *
 * @MetatagTag(
 *   id = "schema_metatag_test_geo_coordinates",
 *   label = @Translation("Schema Metatag Test GeoCoordinates"),
 *   name = "geoCoordinates",
 *   description = @Translation("Test element"),
 *   group = "schema_metatag_test_group",
 *   weight = 0,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "geo_coordinates",
 *   tree_parent = {
 *     "GeoCoordinates",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaMetatagTestGeoCoordinates extends SchemaNameBase {

}
