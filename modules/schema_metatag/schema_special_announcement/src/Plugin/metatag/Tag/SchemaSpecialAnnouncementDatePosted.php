<?php

namespace Drupal\schema_special_announcement\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'datePosted' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_special_announcement_date_posted",
 *   label = @Translation("Date Posted"),
 *   description = @Translation("The date that the COVID-19 announcement was published in ISO-8601 format."),
 *   name = "datePosted",
 *   group = "schema_special_announcement",
 *   weight = 3,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "date",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaSpecialAnnouncementDatePosted extends SchemaNameBase {

}
