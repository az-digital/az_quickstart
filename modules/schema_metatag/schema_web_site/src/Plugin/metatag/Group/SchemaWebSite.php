<?php

namespace Drupal\schema_web_site\Plugin\metatag\Group;

use Drupal\schema_metatag\Plugin\metatag\Group\SchemaGroupBase;

/**
 * Provides a plugin for the 'WebSite' meta tag group.
 *
 * @MetatagGroup(
 *   id = "schema_web_site",
 *   label = @Translation("Schema.org: WebSite"),
 *   description = @Translation("See Schema.org definitions for this Schema type at <a href="":url"">:url</a>. Also see <a href="":url2"">Google's requirements</a>.", arguments = {
 *     ":url" = "https://schema.org/WebSite",
 *     ":url2" = "https://developers.google.com/search/docs/data-types/sitelinks-searchbox",
 *   }),
 *   weight = 10,
 * )
 */
class SchemaWebSite extends SchemaGroupBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
