<?php

namespace Drupal\schema_job_posting\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_job_occupational_category' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_job_posting_occupational_category",
 *   label = @Translation("occupationalCategory"),
 *   description = @Translation("The category of the job. You can use this list of supported categories <a href="":url"" target=""_blank"" rel=""noopener"">:url</a>.", arguments = { ":url" = "https://www.onetcenter.org/taxonomy/2010/list.html"}),
 *   name = "occupationalCategory",
 *   group = "schema_job_posting",
 *   weight = 15,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "text",
 *   tree_parent = {},
 *   tree_depth = -1,
 * )
 */
class SchemaJobPostingOccupationalCategory extends SchemaNameBase {

}
