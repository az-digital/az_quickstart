<?php

namespace Drupal\schema_special_announcement\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Plugin for 'schema_special_announcement_disease_spread_statistics' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_special_announcement_disease_spread_statistics",
 *   label = @Translation("diseaseSpreadStatistics"),
 *   description = @Translation("If applicable to the announcement, url to statistical information about the spread of a disease."),
 *   name = "diseaseSpreadStatistics",
 *   group = "schema_special_announcement",
 *   weight = 11,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "url",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaSpecialAnnouncementDiseaseSpreadStatistics extends SchemaNameBase {

}
