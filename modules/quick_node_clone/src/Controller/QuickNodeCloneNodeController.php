<?php

namespace Drupal\quick_node_clone\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\node\Controller\NodeController;
use Drupal\node\Entity\Node;
use Drupal\quick_node_clone\Entity\QuickNodeCloneEntityFormBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Quick Node Clone Node routes.
 */
class QuickNodeCloneNodeController extends NodeController {

  /**
   * The entity form builder.
   *
   * @var \Drupal\quick_node_clone\Form\QuickNodeCloneEntityFormBuilder
   */
  protected $qncEntityFormBuilder;

  /**
   * The settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * Constructs a NodeController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\quick_node_clone\Entity\QuickNodeCloneEntityFormBuilder $entity_form_builder
   *   The entity form builder.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    DateFormatterInterface $date_formatter,
    RendererInterface $renderer,
    EntityRepositoryInterface $entity_repository,
    QuickNodeCloneEntityFormBuilder $entity_form_builder,
    ConfigFactoryInterface $config_factory,
  ) {
    parent::__construct($date_formatter, $renderer, $entity_repository);
    $this->qncEntityFormBuilder = $entity_form_builder;
    $this->settings = $config_factory->get('quick_node_clone.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('entity.repository'),
      $container->get('quick_node_clone.entity.form_builder'),
      $container->get('config.factory'),
    );
  }

  /**
   * Retrieves the entity form builder.
   *
   * @return \Drupal\quick_node_clone\Form\QuickNodeCloneFormBuilder
   *   The entity form builder.
   */
  protected function entityFormBuilder() {
    return $this->qncEntityFormBuilder;
  }

  /**
   * Provides the node submission form.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node entity to clone.
   *
   * @return array
   *   A node submission form.
   */
  public function cloneNode(Node $node) {
    if (!empty($node)) {
      $form = $this->entityFormBuilder()->getForm($node, 'quick_node_clone');
      return $form;
    }
    else {
      throw new NotFoundHttpException();
    }
  }

  /**
   * The _title_callback for the node.add route.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The current node.
   *
   * @return string
   *   The page title.
   */
  public function clonePageTitle(Node $node) {
    $prepend_text = "";
    if (!empty($this->settings->get('text_to_prepend_to_title'))) {
      $prepend_text = $this->settings->get('text_to_prepend_to_title') . " ";
    }
    return $prepend_text . $node->getTitle();
  }

}
