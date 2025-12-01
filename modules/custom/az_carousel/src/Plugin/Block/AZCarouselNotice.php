<?php

namespace Drupal\az_carousel\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
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
 */
#[Block(
  id: 'az_carousel_notice',
  admin_label: new TranslatableMarkup('Quickstart Carousel Notification Block'),
)]
class AZCarouselNotice extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $link_to_content = [
      '#type' => 'link',
      '#title' => $this->t('Edit Carousel Items'),
      '#url' => Url::fromRoute('system.admin_content', [
        'type' => 'az_carousel_item',
      ]),
      '#attributes' => [
        'class' => [
          'btn',
          'btn-success',
          'mr-4',
        ],
      ],
    ];
    $link_to_reorder_content = [
      '#type' => 'link',
      '#title' => $this->t('Reorder Carousel Items'),
      '#url' => Url::fromRoute('view.az_carousel.reorder_carousel_items'),
      '#attributes' => [
        'class' => [
          'btn',
          'btn-success',
        ],
      ],
    ];
    $callout_markup_header = [
      '#markup' => '<p><strong>' . $this->t('Need to modify your carousel items?') . '</strong></p>',
    ];
    $callout_markup_description = [
      '#markup' => '<p>' . $this->t('Use the buttons below to modify your carousel items.') . '</p>',
    ];
    $callout_markup_links = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      'child' => [
        $link_to_content,
        $link_to_reorder_content,
      ],
    ];

    $callout = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => [
          'callout',
          'callout-success',
        ],
      ],
      'child' => [
        $callout_markup_header,
        $callout_markup_description,
        $callout_markup_links,
      ],
    ];
    $column = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => [
          'col-12',
        ],
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
      ],
      'child' => $row,
    ];

    return $container;
  }

}
