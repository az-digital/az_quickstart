<?php

namespace Drupal\schema_image_object\Plugin\metatag\Group;

use Drupal\schema_metatag\Plugin\metatag\Group\SchemaGroupBase;

/**
 * Provides a plugin for the 'ImageObject' meta tag group.
 *
 * @MetatagGroup(
 *   id = "schema_image_object",
 *   label = @Translation("Schema.org: ImageObject"),
 *   description = @Translation("See Schema.org definitions for this Schema type at <a href="":url"">:url</a>.", arguments = { ":url" = "https://schema.org/ImageObject"}),
 *   weight = 10,
 * )
 */
class SchemaImageObject extends SchemaGroupBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
