<?php

namespace Drupal\schema_special_announcement\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Plugin for 'schema_special_announcement_government_benefits_info' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_special_announcement_government_benefits_info",
 *   label = @Translation("governmentBenefitsInfo"),
 *   description = @Translation("Information about new government benefits in the context of COVID-19, if applicable to the announcement."),
 *   name = "governmentBenefitsInfo",
 *   group = "schema_special_announcement",
 *   weight = 11,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "government_service",
 *   tree_parent = {
 *     "GovernmentService",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaSpecialAnnouncementGovernmentBenefitsInfo extends SchemaNameBase {

}
