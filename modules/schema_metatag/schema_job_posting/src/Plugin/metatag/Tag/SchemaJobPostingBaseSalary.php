<?php

namespace Drupal\schema_job_posting\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_job_posting_base_salary' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_job_posting_base_salary",
 *   label = @Translation("baseSalary"),
 *   description = @Translation("RECOMMENDED BY GOOGLE. The base salary of the position."),
 *   name = "baseSalary",
 *   group = "schema_job_posting",
 *   weight = 0,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "monetary_amount",
 *   tree_parent = {
 *     "MonetaryAmount",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaJobPostingBaseSalary extends SchemaNameBase {

}
