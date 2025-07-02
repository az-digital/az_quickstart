<?php

namespace Drupal\schema_event\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaImageObjectBase;

/**
 * Provides a plugin for the 'image' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_event_image",
 *   label = @Translation("image"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. The primary image for this item."),
 *   name = "image",
 *   group = "schema_event",
 *   weight = 1,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "image_object",
 *   tree_parent = {
 *     "ImageObject",
 *   },
 *   tree_depth = 0,
 * )
 */
class SchemaEventImage extends SchemaImageObjectBase {

}
