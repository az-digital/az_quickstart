<?php

namespace Drupal\schema_how_to\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_how_to_type' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_how_to_type",
 *   label = @Translation("@type"),
 *   description = @Translation("REQUIRED. The type of page."),
 *   name = "@type",
 *   group = "schema_how_to",
 *   weight = -10,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "type",
 *   tree_parent = {
 *     "HowTo",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaHowToType extends SchemaNameBase {

}
