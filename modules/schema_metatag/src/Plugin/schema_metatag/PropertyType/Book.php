<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeBase;

/**
 * Provides a plugin for the 'Book' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "book",
 *   label = @Translation("Book"),
 *   tree_parent = {
 *     "Book",
 *   },
 *   tree_depth = 0,
 *   property_type = "Book",
 *   sub_properties = {
 *     "@type" = {
 *       "id" = "type",
 *       "label" = @Translation("@type"),
 *       "description" = "",
 *     },
 *     "isbn" = {
 *       "id" = "text",
 *       "label" = @Translation("isbn"),
 *       "description" = @Translation(""),
 *     },
 *     "bookEdition" = {
 *       "id" = "text",
 *       "label" = @Translation("bookEdition"),
 *       "description" = @Translation("The edition of the book."),
 *     },
 *     "bookFormat" = {
 *       "id" = "text",
 *       "label" = @Translation("bookFormat"),
 *       "description" = @Translation("The format of the book (comma-separated), i.e. https://schema.org/Hardcover,https://schema.org/Paperback,https://schema.org/EBook"),
 *     },
 *     "author" = {
 *       "id" = "person",
 *       "label" = @Translation("author"),
 *       "description" = @Translation("The author of the work."),
 *       "tree_parent" = {
 *         "Person",
 *       },
 *       "tree_depth" = 0,
 *     },
 *     "potentialAction" = {
 *       "id" = "action",
 *       "label" = @Translation("potentialAction"),
 *       "description" = @Translation("Potential action for the work, like a ReadAction."),
 *       "tree_parent" = {
 *         "ReadAction",
 *       },
 *       "tree_depth" = 0,
 *     },
 *     "name" = {
 *       "id" = "text",
 *       "label" = @Translation("name"),
 *       "description" = @Translation("The name of the work."),
 *     },
 *     "url" = {
 *       "id" = "url",
 *       "label" = @Translation("url"),
 *       "description" = @Translation("Absolute URL of the canonical Web page for the work."),
 *     },
 *     "sameAs" = {
 *       "id" = "url",
 *       "label" = @Translation("sameAs"),
 *       "description" = @Translation("Urls and social media links, comma-separated list of absolute URLs."),
 *     },
 *     "datePublished" = {
 *       "id" = "date",
 *       "label" = @Translation("datePublished"),
 *       "description" = @Translation("Publication date."),
 *     },
 *   },
 * )
 */
class Book extends PropertyTypeBase {

}
