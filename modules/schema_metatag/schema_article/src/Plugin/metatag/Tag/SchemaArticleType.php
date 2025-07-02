<?php

namespace Drupal\schema_article\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_article_description' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_article_type",
 *   label = @Translation("@type"),
 *   description = @Translation("REQUIRED. The type of article."),
 *   name = "@type",
 *   group = "schema_article",
 *   weight = -10,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "type",
 *   tree_parent = {
 *     "Article",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaArticleType extends SchemaNameBase {

}
