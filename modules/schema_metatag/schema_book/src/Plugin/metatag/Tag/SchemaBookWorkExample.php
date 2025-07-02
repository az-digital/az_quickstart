<?php

namespace Drupal\schema_book\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_book_work_example' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_book_work_example",
 *   label = @Translation("workExample"),
 *   description = @Translation("REQUIRED BY GOOGLE. An example of the book."),
 *   name = "workExample",
 *   group = "schema_book",
 *   weight = 10,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "book",
 *   tree_parent = {
 *     "Book",
 *   },
 *   tree_depth = 0,
 * )
 */
class SchemaBookWorkExample extends SchemaNameBase {

}
