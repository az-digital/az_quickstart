<?php

namespace Drupal\schema_article\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'dateModified' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_article_date_modified",
 *   label = @Translation("dateModified"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. Date the article was last modified."),
 *   name = "dateModified",
 *   group = "schema_article",
 *   weight = 4,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "date",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaArticleDateModified extends SchemaNameBase {

}
