<?php

namespace Drupal\metatag_open_graph_products\Plugin\metatag\Group;

use Drupal\metatag\Plugin\metatag\Group\GroupBase;

/**
 * Provides a plugin for the 'Open Graph - Products' meta tag group.
 *
 * @MetatagGroup(
 *   id = "open_graph_products",
 *   label = @Translation("Open Graph - Products"),
 *   description = @Translation("These <a href='https://ogp.me/'>Open Graph meta tags</a> are for describing products.<br><br>The Facebook <a href='https://developers.facebook.com/tools/debug/'>Sharing Debugger</a> lets you preview how your content will look when it's shared to Facebook and debug any issues with your Open Graph tags."),
 *   weight = 0,
 * )
 */
class OpenGraphProducts extends GroupBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
