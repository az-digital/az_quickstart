<?php

namespace Drupal\schema_item_list\Plugin\metatag\Group;

use Drupal\schema_metatag\Plugin\metatag\Group\SchemaGroupBase;

/**
 * Provides a plugin for the 'ItemList' meta tag group.
 *
 * @MetatagGroup(
 *   id = "schema_item_list",
 *   label = @Translation("Schema.org: ItemList"),
 *   description = @Translation("See Schema.org definitions for this Schema type at <a href="":url"">:url</a>. Also see <a href="":url2"">Google's requirements</a>.", arguments = {
 *     ":url" = "https://schema.org/ItemList",
 *     ":url2" = "https://developers.google.com/search/docs/data-types/carousel",
 *   }),
 *   weight = 10,
 * )
 */
class SchemaItemList extends SchemaGroupBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
