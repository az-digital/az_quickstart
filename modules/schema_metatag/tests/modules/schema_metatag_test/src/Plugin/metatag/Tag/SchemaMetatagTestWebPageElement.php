<?php

namespace Drupal\schema_metatag_test\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * A metatag tag for testing.
 *
 * @MetatagTag(
 *   id = "schema_metatag_test_web_page_element",
 *   label = @Translation("Schema Metatag Test WebPageElement"),
 *   name = "webPageElement",
 *   description = @Translation("Test element"),
 *   group = "schema_metatag_test_group",
 *   weight = 0,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "web_page_element",
 *   tree_parent = {
 *     "WebPageElement",
 *   },
 *   tree_depth = -1,
 * )
 */
class SchemaMetatagTestWebPageElement extends SchemaNameBase {

}
