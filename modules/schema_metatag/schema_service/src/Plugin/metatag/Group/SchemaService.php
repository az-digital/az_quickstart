<?php

namespace Drupal\schema_service\Plugin\metatag\Group;

use Drupal\schema_metatag\Plugin\metatag\Group\SchemaGroupBase;

/**
 * Provides a plugin for the 'Service' meta tag group.
 *
 * @MetatagGroup(
 *   id = "schema_service",
 *   label = @Translation("Schema.org: Service"),
 *   description = @Translation("See Schema.org definitions for this Schema type at <a href="":url"">:url</a>.", arguments = { ":url" = "https://schema.org/Service"}),
 *   weight = 10,
 * )
 */
class SchemaService extends SchemaGroupBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
