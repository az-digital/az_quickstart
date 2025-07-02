<?php

namespace Drupal\schema_job_posting\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_job_posting_hiring_organization' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_job_posting_hiring_organization",
 *   label = @Translation("hiringOrganization"),
 *   description = @Translation("REQUIRED BY GOOGLE. The organization offering the job position"),
 *   name = "hiringOrganization",
 *   group = "schema_job_posting",
 *   weight = 0,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "organization",
 *   tree_parent = {
 *     "Organization",
 *   },
 *   tree_depth = 2,
 * )
 */
class SchemaJobPostingHiringOrganization extends SchemaNameBase {

}
