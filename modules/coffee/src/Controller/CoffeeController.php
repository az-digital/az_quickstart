<?php

namespace Drupal\coffee\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Menu\LocalTaskManagerInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Provides route responses for coffee.module.
 */
class CoffeeController extends ControllerBase {

  /**
   * The coffee config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * The local task manager service.
   *
   * @var \Drupal\Core\Menu\LocalTaskManagerInterface
   */
  protected $localTaskManager;

  /**
   * The access manager service.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The url generator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new CoffeeController object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_link_tree
   *   The menu link tree service.
   * @param \Drupal\Core\Menu\LocalTaskManagerInterface $local_task_manager
   *   The local task manager service.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MenuLinkTreeInterface $menu_link_tree, LocalTaskManagerInterface $local_task_manager, AccessManagerInterface $access_manager, UrlGeneratorInterface $url_generator, RouteMatchInterface $route_match) {
    $this->config = $config_factory->get('coffee.configuration');
    $this->menuLinkTree = $menu_link_tree;
    $this->localTaskManager = $local_task_manager;
    $this->accessManager = $access_manager;
    $this->urlGenerator = $url_generator;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('menu.link_tree'),
      $container->get('plugin.manager.menu.local_task'),
      $container->get('access_manager'),
      $container->get('coffee.url_generator'),
      $container->get('current_route_match')
    );
  }

  /**
   * Outputs the data that is used for the Coffee autocompletion in JSON.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The json response.
   */
  public function coffeeData() {
    $output = [];

    foreach ($this->config->get('coffee_menus') as $menu_name) {
      $tree = $this->getMenuTreeElements($menu_name);
      $commands_group = $menu_name == 'account' ? ':user' : NULL;

      foreach ($tree as $tree_element) {
        $link = $tree_element->link;
        try {
          $output[$link->getRouteName()] = [
            'value' => $link->getUrlObject()
              ->setUrlGenerator($this->urlGenerator)
              ->toString(),
            'label' => Html::escape($link->getTitle()),
            'command' => $commands_group,
          ];

          $tasks = $this->getLocalTasksForRoute($link->getRouteName(), $link->getRouteParameters());

          foreach ($tasks as $route_name => $task) {
            if (empty($output[$route_name])) {
              $output[$route_name] = [
                'value' => $task['url']->setUrlGenerator($this->urlGenerator)
                  ->toString(),
                'label' => Html::escape($link->getTitle() . ' - ' . $task['title']),
                'command' => NULL,
              ];
            }
          }
        } catch(\Exception $e) {
          continue;
        }
      }
    }

    $commands = $this->moduleHandler()->invokeAll('coffee_commands');

    if (!empty($commands)) {
      $output = array_merge($output, $commands);
    }

    // Re-index the array.
    $output = array_values($output);

    return new JsonResponse($output);
  }

  /**
   * Retrieves the menu tree elements for the given menu.
   *
   * Every element returned by this method is already access checked.
   *
   * @param string $menu_name
   *   The menu name.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   A flatten array of menu link tree elements for the given menu.
   */
  protected function getMenuTreeElements($menu_name) {
    $parameters = new MenuTreeParameters();
    $tree = $this->menuLinkTree->load($menu_name, $parameters);

    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkNodeAccess'],
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      ['callable' => 'menu.default_tree_manipulators:flatten'],
    ];
    $tree = $this->menuLinkTree->transform($tree, $manipulators);

    // Top-level inaccessible links are *not* removed; it is up
    // to the code doing something with the tree to exclude inaccessible links.
    // @see menu.default_tree_manipulators:checkAccess
    foreach ($tree as $key => $element) {
      if (!$element->access->isAllowed()) {
        unset($tree[$key]);
      }
    }

    return $tree;
  }

  /**
   * Retrieve all the local tasks for a given route.
   *
   * Every element returned by this method is already access checked.
   *
   * @param string $route_name
   *   The route name for which find the local tasks.
   * @param array $route_parameters
   *   The route parameters.
   *
   * @return array
   *   A flatten array that contains the local tasks for the given route.
   *   Each element in the array is keyed by the route name associated with
   *   the local tasks and contains:
   *     - title: the title of the local task.
   *     - url: the url object for the local task.
   *     - localized_options: the localized options for the local task.
   */
  protected function getLocalTasksForRoute($route_name, array $route_parameters) {
    $links = [];

    $tree = $this->localTaskManager->getLocalTasksForRoute($route_name);

    foreach ($tree as $instances) {
      /* @var $instances \Drupal\Core\Menu\LocalTaskInterface[] */
      foreach ($instances as $child) {
        $child_route_name = $child->getRouteName();
        // Merges the parent's route parameter with the child ones since you
        // calculate the local tasks outside of parent route context.
        $child_route_parameters = $child->getRouteParameters($this->routeMatch) + $route_parameters;

        if (strpos($child_route_name, 'config_translate') !== FALSE && $this->accessManager->checkNamedRoute($child_route_name, $child_route_parameters)) {
          $links[$child_route_name] = [
            'title' => $child->getTitle(),
            'url' => Url::fromRoute($child_route_name, $child_route_parameters),
            'localized_options' => $child->getOptions($this->routeMatch),
          ];
        }
      }
    }

    return $links;
  }

}
