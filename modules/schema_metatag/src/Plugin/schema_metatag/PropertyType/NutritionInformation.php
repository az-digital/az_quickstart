<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;

/**
 * Provides a plugin for the 'NutritionInformation' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "nutrition_information",
 *   label = @Translation("NutritionInformation"),
 *   tree_parent = {
 *     "NutritionInformation",
 *   },
 *   tree_depth = -1,
 *   property_type = "NutritionInformation",
 *   sub_properties = {
 *     "@type" = {
 *       "id" = "type",
 *       "label" = @Translation("@type"),
 *       "description" = "",
 *       "tree_parent" = {},
 *       "tree_depth" = -1,
 *     },
 *     "servingSize" = {
 *       "id" = "text",
 *       "label" = @Translation("servingSize"),
 *       "description" = @Translation("The serving size, in terms of the number of volume or mass."),
 *     },
 *     "calories" = {
 *       "id" = "text",
 *       "label" = @Translation("calories"),
 *       "description" = @Translation("The number of calories."),
 *     },
 *     "carbohydrateContent" = {
 *       "id" = "mass",
 *       "label" = @Translation("carbohydrateContent"),
 *       "description" = @Translation("The number of grams of carbohydrates."),
 *     },
 *     "cholesterolContent" = {
 *       "id" = "mass",
 *       "label" = @Translation("cholesterolContent"),
 *       "description" = @Translation("The number of milligrams of cholesterol."),
 *     },
 *     "fiberContent" = {
 *       "id" = "mass",
 *       "label" = @Translation("fiberContent"),
 *       "description" = @Translation("The number of grams of fiber."),
 *     },
 *     "proteinContent" = {
 *       "id" = "mass",
 *       "label" = @Translation("proteinContent"),
 *       "description" = @Translation("The number of grams of protein."),
 *     },
 *     "sodiumContent" = {
 *       "id" = "mass",
 *       "label" = @Translation("sodiumContent"),
 *       "description" = @Translation("The number of milligrams of sodium."),
 *     },
 *     "sugarContent" = {
 *       "id" = "mass",
 *       "label" = @Translation("sugarContent"),
 *       "description" = @Translation("The number of grams of sugar."),
 *     },
 *     "fatContent" = {
 *       "id" = "mass",
 *       "label" = @Translation("fatContent"),
 *       "description" = @Translation("The number of grams of fat."),
 *     },
 *     "saturatedFatContent" = {
 *       "id" = "mass",
 *       "label" = @Translation("saturatedFatContent"),
 *       "description" = @Translation("The number of grams of saturated fat."),
 *     },
 *     "unsaturatedFatContent" = {
 *       "id" = "mass",
 *       "label" = @Translation("unsaturatedFatContent"),
 *       "description" = @Translation("The number of grams of unsaturated fat."),
 *     },
 *     "transFatContent" = {
 *       "id" = "mass",
 *       "label" = @Translation("transFatContent"),
 *       "description" = @Translation("The number of grams of trans fat."),
 *     },
 *   },
 * )
 */
class NutritionInformation extends PropertyTypeBase {

}
