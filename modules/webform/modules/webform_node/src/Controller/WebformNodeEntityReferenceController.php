<?php

namespace Drupal\webform_node\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\node\NodeInterface;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a controller for webform node entity references.
 */
class WebformNodeEntityReferenceController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The webform entity reference manager.
   *
   * @var \Drupal\webform\WebformEntityReferenceManagerInterface
   */
  protected $webformEntityReferenceManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->webformEntityReferenceManager = $container->get('webform.entity_reference_manager');
    return $instance;
  }

  /**
   * Set the current webform for a node with multiple webform attached.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\node\NodeInterface $node
   *   A node.
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to a selected destination or the node's URL.
   */
  public function change(Request $request, NodeInterface $node, WebformInterface $webform) {
    $this->webformEntityReferenceManager->setUserWebformId($node, $webform->id());
    return new RedirectResponse($request->query->get('destination') ?: $node->toUrl()->setAbsolute()->toString());
  }

}
