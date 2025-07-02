<?php

namespace Drupal\schema_special_announcement\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'category' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_special_announcement_category",
 *   label = @Translation("Category"),
 *   description = @Translation("The URL that describes the category for the special announcement. Set the category to the Wikipedia page for COVID-19: https://www.wikidata.org/wiki/Q81068910."),
 *   name = "category",
 *   group = "schema_special_announcement",
 *   weight = 2,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "url",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaSpecialAnnouncementCategory extends SchemaNameBase {

}
