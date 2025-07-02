<?php

namespace Drupal\schema_metatag_test\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * A metatag tag for testing.
 *
 * @MetatagTag(
 *   id = "schema_metatag_test_program_membership",
 *   label = @Translation("Schema Metatag Test ProgramMembership"),
 *   name = "programMembership",
 *   description = @Translation("Test ProgramMembership"),
 *   group = "schema_metatag_test_group",
 *   weight = 0,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "program_membership",
 *   tree_parent = {
 *     "ProgramMembership",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaMetatagTestProgramMembership extends SchemaNameBase {

}
