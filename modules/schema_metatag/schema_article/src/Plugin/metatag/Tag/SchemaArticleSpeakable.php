<?php

namespace Drupal\schema_article\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_web_page_speakable' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_article_speakable",
 *   label = @Translation("speakable"),
 *   description = @Translation("Speakable property."),
 *   name = "speakable",
 *   group = "schema_article",
 *   weight = 5,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "speakable_specification",
 *   tree_parent = {
 *     "SpeakableSpecification",
 *   },
 *   tree_depth = 0,
 * )
 */
class SchemaArticleSpeakable extends SchemaNameBase {

}
