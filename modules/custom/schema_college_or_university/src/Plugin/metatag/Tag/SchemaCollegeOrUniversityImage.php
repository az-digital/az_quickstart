<?php

namespace Drupal\schema_college_or_university\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'image' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_college_or_university_image",
 *   label = @Translation("image"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. The image of the College or University."),
 *   name = "image",
 *   group = "schema_college_or_university",
 *   weight = -35,
 *   type = "image",
 *   property_type = "image_object",
 *   tree_parent = {
 *      "ImageObject",
 *   },
 *   tree_depth = 0,
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class SchemaCollegeOrUniversityImage extends SchemaNameBase {

}
