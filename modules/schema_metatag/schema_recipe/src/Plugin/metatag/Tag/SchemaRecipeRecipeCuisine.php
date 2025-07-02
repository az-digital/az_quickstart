<?php

namespace Drupal\schema_recipe\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_recipe_recipe_cuisine' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_recipe_recipe_cuisine",
 *   label = @Translation("recipeCuisine"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. The region associated with your recipe. For example, 'French Mediterranean', or 'American'."),
 *   name = "recipeCuisine",
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
class SchemaRecipeRecipeCuisine extends SchemaNameBase {

}
