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

    // Build the az_documentation view (default display) or show a fallback.
    $view = Views::getView('az_documentation');
    if ($view && $view->storage->getDisplay('default') && $view->access('default')) {
      $view->setDisplay('default');
      $view->preExecute();
      $view->execute();
      $build['listing'] = $view->buildRenderable('default');
    }
    else {
      $reason = !$view ? 'view not found' : 'default display not accessible';
      $build['listing'] = [
        '#type' => 'container',
        'placeholder' => [
          '#markup' => '<p>' . $this->t('Documentation listing unavailable (@reason). Verify the view config and permissions.', ['@reason' => $reason]) . '</p>',
        ],
      ];
    }

    return $build;
  }

}
