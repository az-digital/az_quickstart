<?php

namespace Drupal\schema_web_site\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_web_site_potential_action' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_web_site_potential_action",
 *   label = @Translation("potentialAction"),
 *   description = @Translation("Potential action that can be accomplished on this site, like SearchAction."),
 *   name = "potentialAction",
 *   group = "schema_web_site",
 *   weight = 5,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "action",
 *   tree_parent = {
 *     "SearchAction",
 *   },
 *   tree_depth = 0,
 * )
 */
class SchemaWebSitePotentialAction extends SchemaNameBase {

}
