<?php

namespace Drupal\schema_article_example\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_article_age_range' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_article_age_range",
 *   label = @Translation("typicalAgeRange"),
 *   description = @Translation("The applicable age range of the item."),
 *   name = "typicalAgeRange",
 *   group = "schema_article",
 *   weight = 10,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "text",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaArticleAgeRange extends SchemaNameBase {

}
