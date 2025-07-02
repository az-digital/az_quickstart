<?php

namespace Drupal\schema_job_posting\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_job_posting_id' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_job_posting_id",
 *   label = @Translation("@id"),
 *   description = @Translation("Globally unique id of the job posting, usually a url."),
 *   name = "@id",
 *   group = "schema_job_posting",
 *   weight = -1,
 *   type = "string",
 *   property_type = "text",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class SchemaJobPostingId extends SchemaNameBase {

}
