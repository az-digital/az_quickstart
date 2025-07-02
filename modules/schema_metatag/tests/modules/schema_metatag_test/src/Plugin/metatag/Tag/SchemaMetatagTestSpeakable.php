<?php

namespace Drupal\schema_metatag_test\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * A metatag tag for testing.
 *
 * @MetatagTag(
 *   id = "schema_metatag_test_speakable",
 *   label = @Translation("Schema Metatag Test SpeakableSpecification"),
 *   name = "speakable",
 *   description = @Translation("Test SpeakableSpecification"),
 *   group = "schema_metatag_test_group",
 *   weight = 0,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "speakable_specification",
 *   tree_parent = {
 *     "SpeakableSpecification",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaMetatagTestSpeakable extends SchemaNameBase {

}
