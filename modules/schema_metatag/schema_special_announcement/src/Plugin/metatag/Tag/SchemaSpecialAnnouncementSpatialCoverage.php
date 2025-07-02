<?php

namespace Drupal\schema_special_announcement\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'spatialCoverage' meta tag.
 *
 * @MetatagTag(
 *   id = "schema_special_announcement_spatial_coverage",
 *   label = @Translation("spatialCoverage"),
 *   description = @Translation("The geographic region that is the focus of the special announcement, if applicable. For example, the announcement may be about a shelter-in-place that affects multiple regions. If the announcement affects both a region and a specific location (for example, a library closure that serves an entire region), use both spatialCoverage and announcementLocation."),
 *   name = "spatialCoverage",
 *   group = "schema_special_announcement",
 *   weight = 12,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "place",
 *   tree_parent = {
 *     "AdministrativeArea",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaSpecialAnnouncementSpatialCoverage extends SchemaNameBase {

}
