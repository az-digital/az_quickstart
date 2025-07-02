<?php

namespace Drupal\schema_organization\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_organization_contact_point' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_organization_contact_point",
 *   label = @Translation("contactPoint"),
 *   description = @Translation("Telephone and other contact point information. See <a href="":url"">Google Corporate Contact</a>.", arguments = {
 *     ":url" = "https://developers.google.com/search/docs/data-types/corporate-contact",
 *   }),
 *   name = "contactPoint",
 *   group = "schema_organization",
 *   weight = 1.2,
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
class SchemaOrganizationContactPoint extends SchemaNameBase {

}
