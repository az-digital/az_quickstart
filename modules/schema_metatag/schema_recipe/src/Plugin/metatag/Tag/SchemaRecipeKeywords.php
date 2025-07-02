<?php

namespace Drupal\schema_recipe\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'keywords' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_recipe_keywords",
 *   label = @Translation("keywords"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. Keywords of the recipe (comma separated). For example, 'winter apple pie, nutmeg crust'"),
 *   name = "keywords",
 *   group = "schema_recipe",
 *   weight = 2,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "text",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaRecipeKeywords extends SchemaNameBase {

}
