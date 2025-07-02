<?php

namespace Drupal\schema_person\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_person_works_for' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_person_works_for",
 *   label = @Translation("worksFor"),
 *   description = @Translation("Organizations that the person works for."),
 *   name = "worksFor",
 *   group = "schema_person",
 *   weight = 11,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "organization",
 *   tree_parent = {
 *     "Organization",
 *   },
 *   tree_depth = 0,
 * )
 */
class SchemaPersonWorksFor extends SchemaNameBase {

}
