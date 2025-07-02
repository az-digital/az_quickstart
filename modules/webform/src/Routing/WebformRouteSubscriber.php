<?php

namespace Drupal\webform\Routing;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Adds the _admin_route option to webform routes.
 */
class WebformRouteSubscriber extends RouteSubscriberBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a WebformShareRouteSubscriber object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory = NULL) {
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory ?: \Drupal::configFactory();
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Set admin route for webform admin routes.
    foreach ($collection->all() as $route) {
      if (!$route->hasOption('_admin_route') && (
          strpos($route->getPath(), '/admin/structure/webform/') === 0
          || strpos($route->getPath(), '/webform/results/') !== FALSE
        )) {
        $route->setOption('_admin_route', TRUE);
      }

      // Change /admin/structure/webform/ to /admin/webform/.
      if ($this->configFactory->get('webform.settings')->get('ui.toolbar_item')) {
        if (strpos($route->getPath(), '/admin/structure/webform') === 0) {
          $path = str_replace('/admin/structure/webform', '/admin/webform', $route->getPath());
          $route->setPath($path);
        }
      }
    }

    // If the webform_share.module is not enabled, remove variant share route.
    if (!$this->moduleHandler->moduleExists('webform_share')) {
      $collection->remove('entity.webform.variant.share_form');
    }
  }

}
