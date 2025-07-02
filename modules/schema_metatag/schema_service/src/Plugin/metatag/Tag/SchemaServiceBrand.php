<?php

namespace Drupal\schema_service\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_service_brand' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_service_brand",
 *   label = @Translation("brand"),
 *   description = @Translation("The brand of the service."),
 *   name = "brand",
 *   group = "schema_service",
 *   weight = 10,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "brand",
 *   tree_parent = {
 *     "Brand",
 *   },
 *   tree_depth = 0,
 * )
 */
class SchemaServiceBrand extends SchemaNameBase {

}
