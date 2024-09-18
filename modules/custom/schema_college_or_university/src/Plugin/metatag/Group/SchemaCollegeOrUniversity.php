<?php

namespace Drupal\schema_college_or_university\Plugin\metatag\Group;

use Drupal\schema_metatag\Plugin\metatag\Group\SchemaGroupBase;

/**
 * Provides a plugin for the 'CollegeOrUniversity' meta tag group.
 *
 * @MetatagGroup(
 *   id = "schema_college_or_university",
 *   label = @Translation("Schema.org: CollegeOrUniversity"),
 *   description = @Translation("See Schema.org definitions for this Schema type at <a href="":url"">:url</a>. Also see <a href="":url2"">Google's requirements</a>.", arguments = {
 *     ":url" = "https://schema.org/CollegeOrUniversity",
 *     ":url2" = "https://developers.google.com/search/docs/data-types/local-business",
 *   }),
 *   weight = 10,
 * )
 */
class SchemaCollegeOrUniversity extends SchemaGroupBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
