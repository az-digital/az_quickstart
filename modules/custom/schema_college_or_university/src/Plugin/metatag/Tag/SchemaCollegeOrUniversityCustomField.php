<?php

namespace Drupal\schema_college_or_university\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaPersonOrgBase;

/**
* Provides a plugin for the 'custom_field' meta tag.
*
* @MetatagTag(
* id = "schema_college_or_university_custom_field",
* label = @Translation("custom_field"),
* description = @Translation("A custom field for demo purposes."),
* name = "custom_field",
* group = "schema_college_or_university",
* weight = 1,
* type = "string",
* secure = FALSE,
* multiple = FALSE,
* property_type = "text",
* tree_parent = {},
* tree_depth = -1,
* )
*/
class SchemaCollegeOrUniversityCustomField extends SchemaPersonOrgBase {
}