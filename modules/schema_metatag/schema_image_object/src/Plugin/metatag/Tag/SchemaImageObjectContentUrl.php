<?php

namespace Drupal\schema_image_object\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_image_object_content_url' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_image_object_content_url",
 *   label = @Translation("contentURL"),
 *   description = @Translation("Absolute URL of the image, i.e. [node:field_name:image_preset_name:url]."),
 *   name = "contentUrl",
 *   group = "schema_image_object",
 *   weight = 0,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "url",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaImageObjectContentUrl extends SchemaNameBase {

}
