<?php

namespace Drupal\az_news_marketing_cloud\Controller;

// Use Drupal\Core\Entity\EntityInterface;
// use Drupal\Core\Entity\Controller\EntityViewController;
// use Symfony\Component\HttpFoundation\Response;.
use Drupal\Core\Controller\ControllerBase;
// Use Drupal\node\NodeInterface;.
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

// Class PageOutputController extends EntityViewController {
// .
/**
 * Returns a render-able array for a test page.
 */
/**
 * Public function view(EntityInterface $node) {
 * $page = parent::view($node);
 * \Drupal::routeMatch()->getParameter('node');
 * return array (
 * '#theme' => 'html__export__marketing_cloud',
 * );
 * $html = \Drupal::service('renderer')->renderRoot($node);
 * $response = new Response();
 * $response->setContent($html);
 * return $response;
 * }.
 *
 * }.
 * Class PageOutputController extends ControllerBase
 * {.
 * public function render(NodeInterface $node)
 * {
 * $fields = [
 * 'title' => $node->getTitle(),
 * 'body' => $node->get('body')->value,
 * 'field_image' => $node->get('field_image')->entity->getUrl(),
 * ];
 * return [
 * '#theme' => 'html__export__marketing_cloud',
 * '#fields' => $fields,
 * ];
 * }
 * }.
 */
class PageOutputController extends ControllerBase {

  protected $entityTypeManager;

  /**
   *
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   *
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  // Public function render(NodeInterface $node) {
  //   $view_builder = $this->entityTypeManager->getViewBuilder('node');
  //   $display = 'az_marketing_cloud_text_layout'; // The name of the view mode you want to use.
  //   $build = $view_builder->view($node, $display);.
  // // Create a render array for the custom template.
  // $output = [
  //   '#theme' => 'html__node__export__marketing_cloud',
  //   '#content' => $build,
  // ];
  // return $output;
  // }.
}
