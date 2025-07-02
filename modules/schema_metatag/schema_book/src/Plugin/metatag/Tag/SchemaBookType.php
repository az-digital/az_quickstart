<?php

namespace Drupal\schema_book\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_book_type' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_book_type",
 *   label = @Translation("@type"),
 *   description = @Translation("REQUIRED. The type of this Book"),
 *   name = "@type",
 *   group = "schema_book",
 *   weight = -10,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "type",
 *   tree_parent = {
 *     "Book",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaBookType extends SchemaNameBase {

}
