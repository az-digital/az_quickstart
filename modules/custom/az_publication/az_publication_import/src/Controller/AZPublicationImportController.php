<?php

namespace Drupal\az_publication_import\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Quickstart Publication Import routes.
 */
class AZPublicationImportController extends ControllerBase {

  /**
   * System Manager Service.
   *
   * @var \Drupal\system\SystemManager
   */
  protected $systemManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();

    $instance->systemManager = $container->get('system.manager');
    return $instance;
  }

  /**
   * Provides a single block from the administration menu as a page.
   */
  public function menuPage() {
    return $this->systemManager->getBlockContents();
  }

}
