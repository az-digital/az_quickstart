<?php

namespace Drupal\schema_special_announcement\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'datePublished' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_special_announcement_expires",
 *   label = @Translation("Expires"),
 *   description = @Translation("The date in which the content expires and is no longer useful or available in ISO-8601 format. Don't include this property if you don't know when the content will expire."),
 *   name = "expires",
 *   group = "schema_special_announcement",
 *   weight = 4,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "date",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaSpecialAnnouncementExpires extends SchemaNameBase {

}
