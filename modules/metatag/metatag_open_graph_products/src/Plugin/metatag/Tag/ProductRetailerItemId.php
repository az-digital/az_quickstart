<?php

namespace Drupal\metatag_open_graph_products\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'product:retailer_item_id' meta tag.
 *
 * @MetatagTag(
 *   id = "product_retailer_item_id",
 *   label = @Translation("Retailer Item ID"),
 *   description = @Translation("The ID of the product as provided by the retailer."),
 *   name = "product:retailer_item_id",
 *   group = "open_graph_products",
 *   weight = 1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class ProductRetailerItemId extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
