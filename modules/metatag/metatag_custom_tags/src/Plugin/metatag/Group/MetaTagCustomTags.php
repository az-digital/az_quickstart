<?php

namespace Drupal\metatag_custom_tags\Plugin\metatag\Group;

use Drupal\metatag\Plugin\metatag\Group\GroupBase;

/**
 * The metatag custom tag group.
 *
 * @MetatagGroup(
 *   id = "metatag_custom_tags",
 *   label = @Translation("Custom tags"),
 *   description = @Translation("These custom tags are designed to use the custom purpose on the website."),
 *   weight = 3
 * )
 */
class MetaTagCustomTags extends GroupBase {
  // Inherits everything from Base.
}
