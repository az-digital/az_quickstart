<?php

namespace Drupal\schema_event\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'doorTime' meta tag.
 *
 * @MetatagTag(
 *   id = "schema_event_door_time",
 *   label = @Translation("doorTime"),
 *   description = @Translation("The time when admission will commence."),
 *   name = "doorTime",
 *   group = "schema_event",
 *   weight = 2,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "date",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaEventDoorTime extends SchemaNameBase {

}
