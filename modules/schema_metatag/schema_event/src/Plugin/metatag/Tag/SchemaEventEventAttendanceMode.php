<?php

namespace Drupal\schema_event\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'eventAttendanceMode' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_event_event_attendance_mode",
 *   label = @Translation("eventAttendanceMode"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. The eventAttendanceMode of the event. Valid values are https://schema.org/OfflineEventAttendanceMode, https://schema.org/MixedEventAttendanceMode, or https://schema.org/OnlineEventAttendanceMode."),
 *   name = "eventAttendanceMode",
 *   group = "schema_event",
 *   weight = 1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "text",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaEventEventAttendanceMode extends SchemaNameBase {

}
