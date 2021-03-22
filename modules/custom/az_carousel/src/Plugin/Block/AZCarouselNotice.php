<?php

namespace Drupal\az_carousel\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides an 'AZCarouselNotice' block.
 *
 * This is a temporary solution until a patch is added to the DraggableViews
 * module that adds a contextual link to reorder content.
 * @link https://www.drupal.org/project/draggableviews/issues/2843858
 *
 * Also an addition to allow a quick link to edit carousel items, since
 * contextual links are not generated in the view. Issue currently unavailable.
 *
 * @Block(
 *  id = "az_carousel_notice",
 *  admin_label = @Translation("Quickstart Carousel Notification Block"),
 * )
 */
class AZCarouselNotice extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    return [
      '#markup' => '<div class="container"><div class="row"><div class="col-12"><div class="callout callout-leaf"><p><strong>Need to edit your carousel items?</strong></p><p>Use the button below to edit your carousel items.</p><p><a class="btn btn-success" href="/admin/content?title=&type=az_carousel_item">Edit Carousel Items</a></p></div></div></div></div>',
    ];
  }

}
