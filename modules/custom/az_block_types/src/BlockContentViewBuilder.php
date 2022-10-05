<?php

namespace Drupal\az_block_types;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\block_content\BlockContentViewBuilder as CoreBlockContentViewBuilder;

/**
 * View builder handler for custom blocks allowing to use templating.
 */
class BlockContentViewBuilder extends CoreBlockContentViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    // Use this to add the theme function, so we can create templates for
    // our custom blocks. This is not allowed in the original implementation.
    return EntityViewBuilder::getBuildDefaults($entity, $view_mode);
  }

}
