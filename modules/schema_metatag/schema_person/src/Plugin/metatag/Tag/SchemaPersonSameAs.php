<?php

namespace Drupal\schema_person\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_person_same_as' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_person_same_as",
 *   label = @Translation("sameAs"),
 *   description = @Translation("REQUIRED BY GOOGLE. A single or an [array] of URLs for the person's official social media profile page(s)."),
 *   name = "sameAs",
 *   group = "schema_person",
 *   weight = -4,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "url",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaPersonSameAs extends SchemaNameBase {

}
