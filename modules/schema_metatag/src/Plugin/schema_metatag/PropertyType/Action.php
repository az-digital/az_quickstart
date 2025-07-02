<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;

/**
 * Provides a plugin for the 'Action' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "action",
 *   label = @Translation("Action"),
 *   tree_parent = {
 *     "Action",
 *   },
 *   tree_depth = -1,
 *   property_type = "Action",
 *   sub_properties = {
 *     "@type" = {
 *       "id" = "type",
 *       "label" = @Translation("@type"),
 *       "description" = "",
 *     },
 *     "target" = {
 *       "id" = "entry_point",
 *       "label" = @Translation("target"),
 *       "description" = @Translation("Indicates a target EntryPoint for an Action."),
 *       "tree_parent" = {
 *         "EntryPoint",
 *       },
 *       "tree_depth" = 0,
 *     },
 *     "result" = {
 *       "id" = "thing",
 *       "label" = @Translation("result"),
 *       "description" = @Translation("The result produced in the action. e.g. John wrote a book."),
 *       "tree_parent" = {
 *         "Thing",
 *       },
 *       "tree_depth" = 2,
 *     },
 *     "expectsAcceptanceOf" = {
 *       "id" = "offer",
 *       "label" = @Translation("expectsAcceptanceOf"),
 *       "description" = @Translation("An Offer which must be accepted before the user can perform the Action. For example, the user may need to buy a movie before being able to watch it."),
 *       "tree_parent" = {
 *         "Thing",
 *       },
 *       "tree_depth" = 2,
 *     },
 *     "query" = {
 *       "id" = "url",
 *       "label" = @Translation("query"),
 *       "description" = @Translation("The query used on this action, i.e. https://query.example.com/search?q={search_term_string}."),
 *     },
 *     "query-input" = {
 *       "id" = "text",
 *       "label" = @Translation("query-input"),
 *       "description" = @Translation("The placeholder for the query, i.e. required name=search_term_string."),
 *     },
 *   },
 * )
 */
class Action extends PropertyTypeBase {

}
