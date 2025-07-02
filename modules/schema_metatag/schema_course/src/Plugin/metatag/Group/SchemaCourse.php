<?php

namespace Drupal\schema_course\Plugin\metatag\Group;

use Drupal\schema_metatag\Plugin\metatag\Group\SchemaGroupBase;

/**
 * Provides a plugin for the 'Course' meta tag group.
 *
 * @MetatagGroup(
 *   id = "schema_course",
 *   label = @Translation("Schema.org: Course"),
 *   description = @Translation("See Schema.org definitions for this Schema type at <a href="":url"">:url</a>. Also see <a href="":url2"">Google's requirements</a>.", arguments = {
 *     ":url" = "https://schema.org/Course",
 *     ":url2" = "https://developers.google.com/search/docs/data-types/course",
 *   }),
 *   weight = 10,
 * )
 */
class SchemaCourse extends SchemaGroupBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
