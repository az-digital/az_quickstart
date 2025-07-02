<?php

namespace Drupal\schema_person\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_person_contact_point' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_person_contact_point",
 *   label = @Translation("contactPoint"),
 *   description = @Translation("Telephone and other contact point information. See <a href="":url"">ContactPoint</a>.", arguments = {
 *     ":url" = "https://schema.org/ContactPoint",
 *   }),
 *   name = "contactPoint",
 *   group = "schema_person",
 *   weight = 0.1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "contact_point",
 *   tree_parent = {
 *     "ContactPoint",
 *   },
 *   tree_depth = 0,
 * )
 */
class SchemaPersonContactPoint extends SchemaNameBase {

}
