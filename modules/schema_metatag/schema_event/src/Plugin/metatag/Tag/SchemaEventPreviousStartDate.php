<?php

namespace Drupal\schema_event\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'previousStartDate' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_event_previous_start_date",
 *   label = @Translation("previousStartDate"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. The previousStartDate of the event."),
 *   name = "previousStartDate",
 *   group = "schema_event",
 *   weight = 4,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "date",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaEventPreviousStartDate extends SchemaNameBase {

}
