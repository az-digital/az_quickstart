<?php

namespace Drupal\devel_generate\Routing;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\devel_generate\Form\DevelGenerateForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides dynamic routes for devel_generate.
 */
class DevelGenerateRoutes implements ContainerInjectionInterface {

  /**
   * The manager to be used for instantiating plugins.
   */
  protected PluginManagerInterface $develGenerateManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = new self();
    $instance->develGenerateManager = $container->get('plugin.manager.develgenerate');

    return $instance;
  }

  /**
   * Define routes for all devel_generate plugins.
   */
  public function routes(): array {
    $devel_generate_plugins = $this->develGenerateManager->getDefinitions();

    $routes = [];
    foreach ($devel_generate_plugins as $id => $plugin) {
      $label = $plugin['label'];
      $type_url_str = str_replace('_', '-', $plugin['url']);
      $routes['devel_generate.' . $id] = new Route(
        'admin/config/development/generate/' . $type_url_str,
        [
          '_form' => DevelGenerateForm::class,
          '_title' => 'Generate ' . $label,
          '_plugin_id' => $id,
        ],
        [
          '_permission' => $plugin['permission'],
        ]
      );
    }

    // Add the route for the 'Generate' admin group on the admin/config page.
    // This also provides the page for all devel_generate links.
    $routes['devel_generate.admin_config_generate'] = new Route(
      '/admin/config/development/generate',
      [
        '_controller' => '\Drupal\system\Controller\SystemController::systemAdminMenuBlockPage',
        '_title' => 'Generate',
      ],
      [
        '_permission' => 'administer devel_generate',
      ]
    );

    return $routes;
  }

}
