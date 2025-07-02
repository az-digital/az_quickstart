<?php

namespace Drupal\schema_product\Plugin\metatag\Group;

use Drupal\schema_metatag\Plugin\metatag\Group\SchemaGroupBase;

/**
 * Provides a plugin for the 'Product' meta tag group.
 *
 * @MetatagGroup(
 *   id = "schema_product",
 *   label = @Translation("Schema.org: Product"),
 *   description = @Translation("See Schema.org definitions for this Schema type at <a href="":url"">:url</a>. Also see <a href="":url2"">Google's requirements</a>.", arguments = {
 *     ":url" = "https://schema.org/Product",
 *     ":url2" = "https://developers.google.com/search/docs/data-types/product",
 *   }),
 *   weight = 10,
 * )
 */
class SchemaProduct extends SchemaGroupBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
