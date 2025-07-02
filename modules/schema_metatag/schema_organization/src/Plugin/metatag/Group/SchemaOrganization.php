<?php

namespace Drupal\schema_organization\Plugin\metatag\Group;

use Drupal\schema_metatag\Plugin\metatag\Group\SchemaGroupBase;

/**
 * Provides a plugin for the 'Organization' meta tag group.
 *
 * @MetatagGroup(
 *   id = "schema_organization",
 *   label = @Translation("Schema.org: Organization"),
 *   description = @Translation("See Schema.org definitions for this Schema type at <a href="":url"">:url</a>. Also see <a href="":url2"">Google's requirements</a>.", arguments = {
 *     ":url" = "https://schema.org/Organization",
 *     ":url2" = "https://developers.google.com/search/docs/data-types/local-business",
 *   }),
 *   weight = 10,
 * )
 */
class SchemaOrganization extends SchemaGroupBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
