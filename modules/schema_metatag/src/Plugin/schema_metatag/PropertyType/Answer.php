<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;

/**
 * Provides a plugin for the 'Answer' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "answer",
 *   label = @Translation("Answer"),
 *   tree_parent = {
 *     "Answer",
 *   },
 *   tree_depth = 0,
 *   property_type = "Answer",
 *   sub_properties = {
 *     "@type" = {
 *       "id" = "type",
 *       "label" = @Translation("@type"),
 *       "description" = "",
 *     },
 *     "text" = {
 *       "id" = "text",
 *       "label" = @Translation("text"),
 *       "description" = @Translation("REQUIRED BY GOOGLE. The full text of the answer."),
 *     },
 *     "url" = {
 *       "id" = "url",
 *       "label" = @Translation("url"),
 *       "description" = @Translation("STRONGLY RECOMMENDED BY GOOGLE. A URL that links directly to this answer."),
 *     },
 *     "upvoteCount" = {
 *       "id" = "number",
 *       "label" = @Translation("upvoteCount"),
 *       "description" = @Translation("RECOMMENDED BY GOOGLE. The total number of votes that this answer has received."),
 *     },
 *     "dateCreated" = {
 *       "id" = "date",
 *       "label" = @Translation("dateCreated"),
 *       "description" = @Translation("RECOMMENDED BY GOOGLE. The date at which the answer was added to the page, in ISO-8601 format."),
 *     },
 *     "author" = {
 *       "id" = "organization",
 *       "label" = @Translation("author"),
 *       "description" = @Translation("RECOMMENDED BY GOOGLE. The author of the answer."),
 *       "tree_parent" = {
 *         "Person",
 *         "Organization",
 *       },
 *       "tree_depth" = 0,
 *     },
 *   },
 * )
 */
class Answer extends PropertyTypeBase {

}
