<?php

namespace Drupal\metatag_facebook\Plugin\metatag\Group;

use Drupal\metatag\Plugin\metatag\Group\GroupBase;

/**
 * The Facebook group.
 *
 * @MetatagGroup(
 *   id = "facebook",
 *   label = @Translation("facebook"),
 *   description = @Translation("A set of meta tags specially for controlling advanced functionality with <a href=':fb'>Facebook</a>.<br><br>The Facebook <a href='https://developers.facebook.com/tools/debug/'>Sharing Debugger</a> lets you preview how your content will look when it's shared to Facebook and debug any issues with your Open Graph tags.", arguments = { ":fb" = "https://www.facebook.com/" }),
 *   weight = 4
 * )
 */
class Facebook extends GroupBase {
  // Inherits everything from Base.
}
