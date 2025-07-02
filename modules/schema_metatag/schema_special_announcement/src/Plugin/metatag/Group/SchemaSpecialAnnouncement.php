<?php

namespace Drupal\schema_special_announcement\Plugin\metatag\Group;

use Drupal\schema_metatag\Plugin\metatag\Group\SchemaGroupBase;

/**
 * Provides a plugin for the 'Special Announcement' meta tag group.
 *
 * @MetatagGroup(
 *   id = "schema_special_announcement",
 *   label = @Translation("Schema.org: SpecialAnnouncement"),
 *   description = @Translation("See Schema.org definitions for this Schema type at <a href="":url"">:url</a>. Also see <a href="":url2"">Google's requirements</a>.", arguments = {
 *     ":url" = "https://schema.org/SpecialAnnouncement",
 *     ":url2" = "https://developers.google.com/search/docs/data-types/special-announcements",
 *   }),
 *   weight = 10,
 * )
 */
class SchemaSpecialAnnouncement extends SchemaGroupBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
