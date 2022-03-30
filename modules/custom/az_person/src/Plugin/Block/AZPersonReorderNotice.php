<?php

namespace Drupal\az_person\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

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

    $callout_markup_header = [
      '#markup' => '<p><strong>' . $this->t('Need to reorder people?') . '</strong></p>',
    ];
    $callout_markup_description = [
      '#markup' => '<p>' . t('Use the button below to reorder the people on this page.') . '</p>',
    ];
    $link_to_reorder_content =  [
      '#type' => 'link',
      '#title' => $this->t('Reorder People'),
      '#url' => Url::fromRoute('view.az_reorder.reorder_people'),
      '#attributes' => [
        'class' => [
          'btn',
          'btn-success',
        ],
      ],
    ];

    $callout = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => [
          'callout',
          'callout-leaf',
        ],
      ],
      'child' => [
        $callout_markup_header,
        $callout_markup_description,
        $link_to_reorder_content,
      ]
    ];

    return $callout;
  }

}
