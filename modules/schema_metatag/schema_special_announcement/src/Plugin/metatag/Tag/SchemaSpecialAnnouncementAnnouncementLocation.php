<?php

namespace Drupal\schema_special_announcement\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'announcementLocation' meta tag.
 *
 * @MetatagTag(
 *   id = "schema_special_announcement_announcement_location",
 *   label = @Translation("announcementLocation"),
 *   description = @Translation("The specific location that is associated with the SpecialAnnouncement. For example, a specific testing facility or business with special opening hours. For a larger geographic region, like a quarantine of an entire region, we recommend that you use spatialCoverage."),
 *   name = "announcementLocation",
 *   group = "schema_special_announcement",
 *   weight = 12,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "place",
 *   tree_parent = {
 *     "Place",
 *   },
 *   tree_depth = 2,
 * )
 */
class SchemaSpecialAnnouncementAnnouncementLocation extends SchemaNameBase {

}
