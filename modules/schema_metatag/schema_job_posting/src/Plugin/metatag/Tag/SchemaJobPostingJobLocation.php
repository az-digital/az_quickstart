<?php

namespace Drupal\schema_job_posting\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_job_location' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_job_posting_job_location",
 *   label = @Translation("location"),
 *   description = @Translation("REQUIRED BY GOOGLE. The location of the job."),
 *   name = "jobLocation",
 *   group = "schema_job_posting",
 *   weight = 5,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE,
 *   property_type = "place",
 *   tree_parent = {
 *     "Place",
 *   },
 *   tree_depth = 2,
 * )
 */
class SchemaJobPostingJobLocation extends SchemaNameBase {

}
