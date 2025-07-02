<?php

namespace Drupal\schema_image_object\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_image_object_height' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_image_object_height",
 *   label = @Translation("height"),
 *   description = @Translation("The height of the image."),
 *   name = "height",
 *   group = "schema_image_object",
 *   weight = 5,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "number",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaImageObjectHeight extends SchemaNameBase {

}
