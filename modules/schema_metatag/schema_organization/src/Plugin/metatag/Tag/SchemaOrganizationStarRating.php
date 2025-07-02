<?php

namespace Drupal\schema_organization\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_organization_star_rating' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_organization_star_rating",
 *   label = @Translation("starRating"),
 *   description = @Translation("An official rating for a lodging business or food establishment, e.g. from national associations or standards bodies."),
 *   name = "starRating",
 *   group = "schema_organization",
 *   weight = 11,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "rating",
 *   tree_parent = {
 *     "Rating",
 *   },
 *   tree_depth = 0,
 * )
 */
class SchemaOrganizationStarRating extends SchemaNameBase {

}
