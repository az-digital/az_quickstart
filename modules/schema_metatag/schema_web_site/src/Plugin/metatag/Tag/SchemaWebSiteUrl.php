<?php

namespace Drupal\schema_web_site\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_web_site_url' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_web_site_url",
 *   label = @Translation("url"),
 *   description = @Translation("The url of the web site."),
 *   name = "url",
 *   group = "schema_web_site",
 *   weight = 2,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "url",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaWebSiteUrl extends SchemaNameBase {

}
