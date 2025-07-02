<?php

namespace Drupal\schema_article\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_article_main_entity_of_page' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_article_main_entity_of_page",
 *   label = @Translation("mainEntityOfPage"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. The canonical URL of the article page. Specify mainEntityOfPage when the article is the primary topic of the article page."),
 *   name = "mainEntityOfPage",
 *   group = "schema_article",
 *   weight = 10,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "url",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaArticleMainEntityOfPage extends SchemaNameBase {

}
