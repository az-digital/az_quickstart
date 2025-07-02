<?php

namespace Drupal\schema_recipe\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'recipeCategory' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_recipe_recipe_category",
 *   label = @Translation("recipeCategory"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. The category of the recipe—for example, appetizer, entree, etc."),
 *   name = "recipeCategory",
 *   group = "schema_recipe",
 *   weight = 6,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "text",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaRecipeRecipeCategory extends SchemaNameBase {

}
