<?php

namespace Drupal\schema_special_announcement\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'text' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_special_announcement_text",
 *   label = @Translation("Text"),
 *   description = @Translation("You must include either text, or one of the following urls that point to more info about the announcement, depending on the subject matter: diseasePreventionInfo, diseaseSpreadStatistics, gettingTestedInfo, governmentBenefitsInfo, newsUpdatesAndGuidelines, publicTransportClosuresInfo, quarantineGuidelines, schoolClosuresInfo, and/or travelBans."),
 *   name = "text",
 *   group = "schema_special_announcement",
 *   weight = 1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "text",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaSpecialAnnouncementText extends SchemaNameBase {

}
