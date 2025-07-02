<?php

namespace Drupal\metatag_open_graph_products\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'product:availability' meta tag.
 *
 * @MetatagTag(
 *   id = "product_availability",
 *   label = @Translation("Product availability"),
 *   description = @Translation("The availability of the product."),
 *   name = "product:availability",
 *   group = "open_graph_products",
 *   weight = 1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class ProductAvailability extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
