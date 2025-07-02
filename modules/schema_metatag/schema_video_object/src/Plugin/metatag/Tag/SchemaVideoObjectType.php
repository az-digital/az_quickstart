<?php

namespace Drupal\schema_video_object\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_video_object_type' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_video_object_type",
 *   label = @Translation("@type"),
 *   description = @Translation("REQUIRED. The type of VideoObject"),
 *   name = "@type",
 *   group = "schema_video_object",
 *   weight = -10,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "type",
 *   tree_parent = {
 *     "VideoObject",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaVideoObjectType extends SchemaNameBase {

}
