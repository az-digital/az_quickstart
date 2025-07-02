<?php

namespace Drupal\schema_web_page\Plugin\metatag\Group;

use Drupal\schema_metatag\Plugin\metatag\Group\SchemaGroupBase;

/**
 * Provides a plugin for the 'WebPage' meta tag group.
 *
 * @MetatagGroup(
 *   id = "schema_web_page",
 *   label = @Translation("Schema.org: WebPage"),
 *   description = @Translation("See Schema.org definitions for this Schema type at <a href="":url"">:url</a>. Also see <a href="":url2"">Google's requirements</a>.", arguments = {
 *     ":url" = "https://schema.org/WebPage",
 *     ":url2" = "https://developers.google.com/search/docs/data-types/article",
 *   }),
 *   weight = 10,
 * )
 */
class SchemaWebPage extends SchemaGroupBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
