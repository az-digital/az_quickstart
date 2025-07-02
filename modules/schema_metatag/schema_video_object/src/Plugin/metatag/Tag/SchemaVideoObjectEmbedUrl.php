<?php

namespace Drupal\schema_video_object\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_video_object_embed_url' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_video_object_embed_url",
 *   label = @Translation("embedUrl"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. A URL pointing to a player for the specific video. Usually this is the information in the src element of an <embed> tag."),
 *   name = "embedUrl",
 *   group = "schema_video_object",
 *   weight = 0,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "url",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaVideoObjectEmbedUrl extends SchemaNameBase {

}
