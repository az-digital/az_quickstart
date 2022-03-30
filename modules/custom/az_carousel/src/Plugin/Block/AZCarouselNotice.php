<?php

namespace Drupal\az_carousel\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

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
    $link_to_content =  [
      '#type' => 'link',
      '#title' => $this->t('Edit Carousel Items'),
      '#url' => Url::fromRoute('examples.description', [
        'query' => [
          'title', => ''.
          'type' => 'az_carousel_item',
        ],
      ]),
      '#attributes' => [
        'class' => [
          'btn',
          'btn-success',
        ],
      ],
    ];
    $callout_markup =  '<p><strong>' . t('Need to modify your carousel items?') . '</strong></p>';
    $callout_markup .= '<p>' . t('Use the buttons below to modify your carousel items.') . '</p>';
    $callout_markup .= '<p><a class="btn btn-success" href="/admin/content?title=&type=az_carousel_item">Edit Carousel Items</a>&nbsp;<a class="btn btn-success" href="/admin/az-carousel/reorder-carousel-items">Reorder Carousel Items</a></p>';

    $callout = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => [
          'callout',
          'callout-leaf',
        ],
      'child' => $callout_markup,
    ];
    $column = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => [
          'col-12',
        ],
        'child' => $callout,
    ];
    $row = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => [
          'row',
        ],
      'child' => $column,
    ];
    $container = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => [
          'container',
        ],
      'child' => $row,
    ];


    return [
      '#markup' => '<div class="container"><div class="row"><div class="col-12"><div class="callout callout-leaf"><p><strong>Need to modify your carousel items?</strong></p><p>Use the buttons below to modify your carousel items.</p><p><a class="btn btn-success" href="/admin/content?title=&type=az_carousel_item">Edit Carousel Items</a>&nbsp;<a class="btn btn-success" href="/admin/az-carousel/reorder-carousel-items">Reorder Carousel Items</a></p></div></div></div></div>',
    ];
  }

}
