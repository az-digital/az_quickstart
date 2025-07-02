<?php

namespace Drupal\schema_image_object\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'type' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_image_object_type",
 *   label = @Translation("@type"),
 *   description = @Translation("REQUIRED. The type of this ImageObject."),
 *   name = "@type",
 *   group = "schema_image_object",
 *   weight = -10,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "type",
 *   tree_parent = {
 *     "ImageObject",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaImageObjectType extends SchemaNameBase {

}
