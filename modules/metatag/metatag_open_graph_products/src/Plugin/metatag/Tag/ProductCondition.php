<?php

namespace Drupal\metatag_open_graph_products\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'product:condition' meta tag.
 *
 * @MetatagTag(
 *   id = "product_condition",
 *   label = @Translation("Product condition"),
 *   description = @Translation("The condition of the product."),
 *   name = "product:condition",
 *   group = "open_graph_products",
 *   weight = 1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class ProductCondition extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
