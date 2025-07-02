<?php

namespace Drupal\schema_book\Plugin\metatag\Group;

use Drupal\schema_metatag\Plugin\metatag\Group\SchemaGroupBase;

/**
 * Provides a plugin for the 'Book' meta tag group.
 *
 * @MetatagGroup(
 *   id = "schema_book",
 *   label = @Translation("Schema.org: Book"),
 *   description = @Translation("See Schema.org definitions for this Schema type at <a href="":url"">:url</a>. Also see <a href="":url2"">Google's requirements</a>.", arguments = {
 *     ":url" = "https://schema.org/Book",
 *     ":url2" = "https://developers.google.com/search/docs/data-types/book",
 *   }),
 *   weight = 10,
 * )
 */
class SchemaBook extends SchemaGroupBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
