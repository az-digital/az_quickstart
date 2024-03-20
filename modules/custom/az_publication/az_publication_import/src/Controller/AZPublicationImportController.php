<?php

namespace Drupal\az_publication_import\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\system\SystemManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Returns responses for Quickstart Publication Import routes.
 */
class AZPublicationImportController extends ControllerBase {

  /**
   * Constructs a new \Drupal\az_publication_import\Controller object.
   */
  public function __construct(
    #[Autowire(service: 'system.manager')]
    protected SystemManager $systemManager,
  ) {}

  /**
   * Provides a single block from the administration menu as a page.
   */
  public function menuPage() {
    return $this->systemManager->getBlockContents();
  }

}
