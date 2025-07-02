<?php

namespace Drupal\schema_video_object\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_video_object_upload_date' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_video_object_upload_date",
 *   label = @Translation("uploadDate"),
 *   description = @Translation("REQUIRED BY GOOGLE. The date the video was first published, in ISO 8601 format."),
 *   name = "uploadDate",
 *   group = "schema_video_object",
 *   weight = -1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "date",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaVideoObjectUploadDate extends SchemaNameBase {

}
