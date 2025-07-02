<?php

namespace Drupal\schema_video_object\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_video_object_expires' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_video_object_expires",
 *   label = @Translation("expires"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. If applicable, the date after which the video will no longer be available, in ISO 8601 format. Don't supply this information if your video does not expire."),
 *   name = "expires",
 *   group = "schema_video_object",
 *   weight = 0,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "date",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaVideoObjectExpires extends SchemaNameBase {

}
