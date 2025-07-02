<?php

namespace Drupal\schema_person\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaImageObjectBase;

/**
 * Provides a plugin for the 'schema_person_image' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_person_image",
 *   label = @Translation("image"),
 *   description = @Translation("The primary image for the person."),
 *   name = "image",
 *   group = "schema_person",
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
class SchemaPersonImage extends SchemaImageObjectBase {

}
