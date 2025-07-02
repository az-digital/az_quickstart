<?php

namespace Drupal\schema_job_posting\Plugin\metatag\Group;

use Drupal\schema_metatag\Plugin\metatag\Group\SchemaGroupBase;

/**
 * Provides a plugin for the JobPosting meta tag group.
 *
 * @MetatagGroup(
 *   id = "schema_job_posting",
 *   label = @Translation("Schema.org: JobPosting"),
 *   description = @Translation("See Schema.org definitions for this Schema type at <a href="":url"">:url</a>. Also see <a href="":url2"">Google's requirements</a>.", arguments = {
 *     ":url" = "https://schema.org/JobPosting",
 *     ":url2" = "https://developers.google.com/search/docs/data-types/job-posting",
 *   }),
 *   weight = 10,
 * )
 */
class SchemaJobPosting extends SchemaGroupBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
