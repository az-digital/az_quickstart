<?php

namespace Drupal\az_documentation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\node\Entity\NodeType;
use Drupal\views\Views;

/**
 * Controller for the site documentation hub page.
 */
class DocumentationHubController extends ControllerBase {

  /**
   * Builds the documentation hub page.
   */
  public function build() {
    $build = [
      '#title' => $this->t('Site Documentation'),
    ];

    $intro = $this->t('Use this area to create and organize documentation about custom site features, configuration decisions, editorial workflows, and testing guidance. Any content type can be used. Flexible Pages are often helpful for structured docs.');

    $links = [];
    $account = $this->currentUser();
    foreach (NodeType::loadMultiple() as $type_id => $type) {
      if ($account->hasPermission("create {$type_id} content")) {
        $links[] = Link::createFromRoute($type->label(), 'node.add', ['node_type' => $type_id])->toRenderable();
      }
    }

    $build['intro'] = [
      '#type' => 'container',
      'text' => [
        '#markup' => '<p>' . $intro . '</p>',
      ],
    ];

    $build['create_links'] = $links ? [
      '#theme' => 'item_list',
      '#title' => $this->t('Create documentation using:'),
      '#items' => $links,
    ] : [
      '#markup' => Markup::create('<p>' . $this->t('You do not have permission to create any content types.') . '</p>'),
    ];

    // Programmatically build the view so we can gracefully fallback.
    $view = Views::getView('az_documentation');
    $built = FALSE;
    if ($view) {
      foreach (['page', 'default'] as $display_id) {
        if ($view->storage->getDisplay($display_id) && $view->access($display_id)) {
          $view->setDisplay($display_id);
          $view->preExecute();
          $view->execute();
          $build['listing'] = $view->buildRenderable($display_id);
          $built = TRUE;
          break;
        }
      }
    }
    if (!$built) {
      $reason = !$view ? 'view not found' : 'no accessible display (page/default)';
      $build['listing'] = [
        '#type' => 'container',
        'placeholder' => [
          '#markup' => '<p>' . $this->t('Documentation listing unavailable (@reason). Verify the view config is imported and permissions granted.', ['@reason' => $reason]) . '</p>',
        ],
      ];
    }

    return $build;
  }

}
