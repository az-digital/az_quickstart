<?php

namespace Drupal\schema_article\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_article_has_part' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_article_has_part",
 *   label = @Translation("hasPart"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. Use for <a href="":url"">Paywalled content</a>.", arguments = {
 *     ":url" = "https://developers.google.com/search/docs/data-types/paywalled-content",
 *   }),
 *   name = "hasPart",
 *   group = "schema_article",
 *   weight = 4,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "web_page_element",
 *   tree_parent = {
 *     "WebPageElement",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaArticleHasPart extends SchemaNameBase {

}
