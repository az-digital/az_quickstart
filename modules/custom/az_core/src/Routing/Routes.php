<?php

namespace Drupal\az_core\Routing;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines dynamic routes.
 */
class Routes implements ContainerInjectionInterface {

  use AutowireTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Instantiates a Routes object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $routes = [];

    $config = $this->configFactory->get('az_core.settings');
    if ($config->get('monitoring_page.enabled')) {
      $path = $config->get('monitoring_page.path');
      $routes['az_core.monitoring_page'] = new Route(
        $path,
        [
          '_controller' => 'Drupal\az_core\Controller\MonitoringPageController::deliver',
          '_title' => 'Monitoring Page',
        ],
        [
          '_access' => 'TRUE',
        ]
      );
    }

    return $routes;
  }

}
