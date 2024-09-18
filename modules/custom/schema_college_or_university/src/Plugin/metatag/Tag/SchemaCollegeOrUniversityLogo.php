<?php

namespace Drupal\schema_college_or_university\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaImageObjectBase;

/**
 * Provides a plugin for the 'logo' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_college_or_university_logo",
 *   label = @Translation("logo"),
 *   description = @Translation("The logo for this college_or_university."),
 *   name = "logo",
 *   group = "schema_college_or_university",
 *   weight = 3,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "image_object",
 *   tree_parent = {
 *     "ImageObject",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaCollegeOrUniversityLogo extends SchemaImageObjectBase {

}
