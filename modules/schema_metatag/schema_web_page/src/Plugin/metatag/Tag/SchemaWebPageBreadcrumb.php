<?php

namespace Drupal\schema_web_page\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_web_page_breadcrumb' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_web_page_breadcrumb",
 *   label = @Translation("breadcrumb"),
 *   description = @Translation("Add the breadcrumb for the current web page to Schema.org structured data?"),
 *   name = "breadcrumb",
 *   group = "schema_web_page",
 *   weight = 1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE,
 *   property_type = "breadcrumb_list",
 *   tree_parent = {
 *     "BreadcrumbList",
 *   },
 *   tree_depth = 0,
 * )
 */
class SchemaWebPageBreadcrumb extends SchemaNameBase {

}
