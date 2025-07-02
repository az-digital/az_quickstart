<?php

namespace Drupal\schema_video_object\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_video_object_duration' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_video_object_duration",
 *   label = @Translation("duration"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. The video duration in seconds or ISO 8601 format, i.e. PT1H30M."),
 *   name = "duration",
 *   group = "schema_video_object",
 *   weight = 0,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "duration",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaVideoObjectDuration extends SchemaNameBase {

}
