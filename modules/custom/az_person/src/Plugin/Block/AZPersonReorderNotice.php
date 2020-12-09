<?php

namespace Drupal\az_person\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides an 'AZPersonReorderNotice' block.
 *
 * This is a temporary solution until a patch is added to the DraggableViews
 * module that adds a contextual link to reorder content.
 * @link https://www.drupal.org/project/draggableviews/issues/2843858
 *
 * @Block(
 *  id = "az_person_reorder_notice",
 *  admin_label = @Translation("Quickstart Person Reorder Notification Block"),
 * )
 */
class AZPersonReorderNotice extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    return [
      '#markup' => '<div class="callout callout-leaf"><p><strong>Need to reorder people?</strong></p><p>Use the button below to reorder the people on this page.</p><p><a class="btn btn-success" href="/admin/az-person/reorder-people">Reorder People</a></p></div>',
    ];
  }

}
