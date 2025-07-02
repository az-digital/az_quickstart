<?php

namespace Drupal\schema_event\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'eventStatus' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_event_event_status",
 *   label = @Translation("eventStatus"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. The status of the event. Valid options are https://schema.org/EventCancelled, https://schema.org/EventMovedOnline, https://schema.org/EventPostponed, https://schema.org/EventRescheduled, or https://schema.org/EventScheduled."),
 *   name = "eventStatus",
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
class SchemaEventEventStatus extends SchemaNameBase {

}
