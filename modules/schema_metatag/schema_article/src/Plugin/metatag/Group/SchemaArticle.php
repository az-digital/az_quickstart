<?php

namespace Drupal\schema_article\Plugin\metatag\Group;

use Drupal\schema_metatag\Plugin\metatag\Group\SchemaGroupBase;

/**
 * Provides a plugin for the 'Article' meta tag group.
 *
 * @MetatagGroup(
 *   id = "schema_article",
 *   label = @Translation("Schema.org: Article"),
 *   description = @Translation("See Schema.org definitions for this Schema type at <a href="":url"">:url</a>. Also see <a href="":url2"">Google's requirements</a>.", arguments = {
 *     ":url" = "https://schema.org/Article",
 *     ":url2" = "https://developers.google.com/search/docs/data-types/article",
 *   }),
 *   weight = 10
 * )
 */
class SchemaArticle extends SchemaGroupBase {

}
