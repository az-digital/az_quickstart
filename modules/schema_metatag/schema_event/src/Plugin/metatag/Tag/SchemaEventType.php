<?php

namespace Drupal\schema_event\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'type' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_event_type",
 *   label = @Translation("@type"),
 *   description = @Translation("REQUIRED. The type of event."),
 *   name = "@type",
 *   group = "schema_event",
 *   weight = -10,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "type",
 *   tree_parent = {
 *     "Event",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaEventType extends SchemaNameBase {

}
