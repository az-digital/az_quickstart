<?php

namespace Drupal\schema_web_site\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_web_page_type' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_web_site_type",
 *   label = @Translation("@type"),
 *   description = @Translation("REQUIRED. The type of web site."),
 *   name = "@type",
 *   group = "schema_web_site",
 *   weight = -5,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "type",
 *   tree_parent = {
 *     "WebSite",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaWebSiteType extends SchemaNameBase {

}
