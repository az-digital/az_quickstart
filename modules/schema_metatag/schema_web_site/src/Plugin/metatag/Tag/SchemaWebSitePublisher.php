<?php

namespace Drupal\schema_web_site\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_web_site_publisher' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_web_site_publisher",
 *   label = @Translation("publisher"),
 *   description = @Translation("The publisher of the web site."),
 *   name = "publisher",
 *   group = "schema_web_site",
 *   weight = 10,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "organization",
 *   tree_parent = {
 *     "Person",
 *     "Organization",
 *   },
 *   tree_depth = 0,
 * )
 */
class SchemaWebSitePublisher extends SchemaNameBase {

}
