<?php

namespace Drupal\schema_event\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_event_location' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_event_location",
 *   label = @Translation("location"),
 *   description = @Translation("REQUIRED BY GOOGLE. The location of the event."),
 *   name = "location",
 *   group = "schema_event",
 *   weight = 5,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "place",
 *   tree_parent = {
 *     "Place",
 *     "VirtualLocation",
 *   },
 *   tree_depth = 2,
 * )
 */
class SchemaEventLocation extends SchemaNameBase {

}
