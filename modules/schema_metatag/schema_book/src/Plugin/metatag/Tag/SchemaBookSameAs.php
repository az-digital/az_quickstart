<?php

namespace Drupal\schema_book\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_book_same_as' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_book_same_as",
 *   label = @Translation("sameAs"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. URL of a reference Web page that unambiguously indicates the item's identity. E.g. the URL of the item's Wikipedia page, Wikidata entry, or official website."),
 *   name = "sameAs",
 *   group = "schema_book",
 *   weight = 0,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "url",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaBookSameAs extends SchemaNameBase {

}
