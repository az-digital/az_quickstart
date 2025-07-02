<?php

namespace Drupal\schema_event\Plugin\metatag\Group;

use Drupal\schema_metatag\Plugin\metatag\Group\SchemaGroupBase;

/**
 * Provides a plugin for the 'Event' meta tag group.
 *
 * @MetatagGroup(
 *   id = "schema_event",
 *   label = @Translation("Schema.org: Event"),
 *   description = @Translation("See Schema.org definitions for this Schema type at <a href="":url"">:url</a>. Also see <a href="":url2"">Google's requirements</a>.", arguments = {
 *     ":url" = "https://schema.org/Event",
 *     ":url2" = "https://developers.google.com/search/docs/data-types/event",
 *   }),
 *   weight = 10,
 * )
 */
class SchemaEvent extends SchemaGroupBase {

}
