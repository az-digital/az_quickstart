<?php

namespace Drupal\az_news_marketing_cloud\Controller;

// use Drupal\Core\Entity\EntityInterface;
// use Drupal\Core\Entity\Controller\EntityViewController;
// use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;

// class PageOutputController extends EntityViewController {
// 
  /**
   * Returns a render-able array for a test page.
   */
  // public function view(EntityInterface $node) {
    // $page = parent::view($node);
    // \Drupal::routeMatch()->getParameter('node');
    // return array (
    //   '#theme' => 'html__export__marketing_cloud',
    // ); 
    // $html = \Drupal::service('renderer')->renderRoot($node);
    // $response = new Response();
    // $response->setContent($html);
    // return $response;
  // }
//
// }


class PageOutputController extends ControllerBase {

  public function render(NodeInterface $node) {
    $fields = [
      'title' => $node->getTitle(),
      'body' => $node->get('body')->value,
      'field_image' => $node->get('field_image')->entity->getUrl(),
    ];
    return [
      '#theme' => 'html__export__marketing_cloud',
      '#fields' => $fields,
    ];
  }

}