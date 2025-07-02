<?php

namespace Drupal\schema_job_posting\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'datePosted' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_job_posting_date_posted",
 *   label = @Translation("datePosted"),
 *   description = @Translation("REQUIRED BY GOOGLE. Date and time when the job was posted."),
 *   name = "datePosted",
 *   group = "schema_job_posting",
 *   weight = -5,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "date",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaJobPostingDatePosted extends SchemaNameBase {

}
