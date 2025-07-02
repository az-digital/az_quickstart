<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;

/**
 * Provides a plugin for the 'Question' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "question",
 *   label = @Translation("Question"),
 *   tree_parent = {
 *     "Question",
 *   },
 *   tree_depth = 0,
 *   property_type = "Question",
 *   sub_properties = {
 *     "@type" = {
 *       "id" = "type",
 *       "label" = @Translation("@type"),
 *       "description" = "",
 *     },
 *     "name" = {
 *       "id" = "text",
 *       "label" = @Translation("name"),
 *       "description" = @Translation("REQUIRED BY GOOGLE. The full text of the short form of the question. For example, ""How many teaspoons in a cup?""."),
 *     },
 *     "text" = {
 *       "id" = "text",
 *       "label" = @Translation("text"),
 *       "description" = @Translation("RECOMMENDED BY GOOGLE. The full text of the long form of the question."),
 *     },
 *     "upvoteCount" = {
 *       "id" = "number",
 *       "label" = @Translation("upvoteCount"),
 *       "description" = @Translation("RECOMMENDED BY GOOGLE. The total number of votes that this question has received."),
 *     },
 *     "answerCount" = {
 *       "id" = "number",
 *       "label" = @Translation("answerCount"),
 *       "description" = @Translation("REQUIRED BY GOOGLE. The total number of answers to the question. This may also be 0 for questions with no answers."),
 *     },
 *     "dateCreated" = {
 *       "id" = "date",
 *       "label" = @Translation("dateCreated"),
 *       "description" = @Translation("RECOMMENDED BY GOOGLE. The date at which the question was added to the page, in ISO-8601 format."),
 *     },
 *     "acceptedAnswer" = {
 *       "id" = "answer",
 *       "label" = @Translation("acceptedAnswer"),
 *       "description" = @Translation("A top answer to the question. There can be zero or more of these per question. Either acceptedAnswer OR suggestedAnswer is REQUIRED BY GOOGLE."),
 *       "tree_parent" = {
 *         "Answer",
 *       },
 *       "tree_depth" = 0,
 *     },
 *     "suggestedAnswer" = {
 *       "id" = "answer",
 *       "label" = @Translation("suggestedAnswer"),
 *       "description" = @Translation("One possible answer, but not accepted as a top answer (acceptedAnswer). There can be zero or more of these per Question. Either acceptedAnswer OR suggestedAnswer is REQUIRED BY GOOGLE."),
 *       "tree_parent" = {
 *         "Answer",
 *       },
 *       "tree_depth" = 0,
 *     },
 *     "author" = {
 *       "id" = "organization",
 *       "label" = @Translation("author"),
 *       "description" = @Translation("RECOMMENDED BY GOOGLE. The author of the question."),
 *       "tree_parent" = {
 *         "Person",
 *         "Organization",
 *       },
 *       "tree_depth" = 0,
 *     },
 *   },
 * )
 */
class Question extends PropertyTypeBase {

}
