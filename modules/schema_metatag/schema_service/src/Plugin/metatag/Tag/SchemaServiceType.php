<?php

namespace Drupal\schema_service\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'type' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_service_type",
 *   label = @Translation("@type"),
 *   description = @Translation("REQUIRED. The type of service."),
 *   name = "@type",
 *   group = "schema_service",
 *   weight = -10,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "type",
 *   tree_parent = {
 *     "Service",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaServiceType extends SchemaNameBase {

}
