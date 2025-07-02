<?php

namespace Drupal\schema_place\Plugin\metatag\Group;

use Drupal\schema_metatag\Plugin\metatag\Group\SchemaGroupBase;

/**
 * Provides a plugin for the 'Place' meta tag group.
 *
 * @MetatagGroup(
 *   id = "schema_place",
 *   label = @Translation("Schema.org: Place"),
 *   description = @Translation("See Schema.org definitions for this Schema type at <a href="":url"">:url</a>.", arguments = { ":url" = "https://schema.org/Place"}),
 *   weight = 10,
 * )
 */
class SchemaPlace extends SchemaGroupBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
