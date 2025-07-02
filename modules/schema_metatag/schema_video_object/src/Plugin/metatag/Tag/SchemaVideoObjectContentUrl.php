<?php

namespace Drupal\schema_video_object\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_video_object_content_url' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_video_object_content_url",
 *   label = @Translation("contentUrl"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. A URL pointing to the actual video media file. This file should be in .mpg, .mpeg, .mp4, .m4v, .mov, .wmv, .asf, .avi, .ra, .ram, .rm, .flv, or other video file format. All files must be accessible via HTTP. Metafiles that require a download of the source via streaming protocols, such as RTMP, are not supported. Providing this file allows Google to generate video thumbnails and video previews and can help Google verify your video."),
 *   name = "contentUrl",
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
class SchemaVideoObjectContentUrl extends SchemaNameBase {

}
