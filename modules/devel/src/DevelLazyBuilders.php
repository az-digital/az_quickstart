<?php

namespace Drupal\devel;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Lazy builders for the devel module.
 */
class DevelLazyBuilders implements TrustedCallbackInterface {

  /**
   * The menu link tree service.
   */
  protected MenuLinkTreeInterface $menuLinkTree;

  /**
   * The devel toolbar config.
   */
  protected ImmutableConfig $config;

  /**
   * Constructs a new ShortcutLazyBuilders object.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_link_tree
   *   The menu link tree service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    MenuLinkTreeInterface $menu_link_tree,
    ConfigFactoryInterface $config_factory,
  ) {
    $this->menuLinkTree = $menu_link_tree;
    $this->config = $config_factory->get('devel.toolbar.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return ['renderMenu'];
  }

  /**
   * Lazy builder callback for the devel menu toolbar.
   *
   * @return array
   *   The renderable array rapresentation of the devel menu.
   */
  public function renderMenu(): array {
    $parameters = new MenuTreeParameters();
    $parameters->onlyEnabledLinks()->setTopLevelOnly();

    $tree = $this->menuLinkTree->load('devel', $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      [
        'callable' => function (array $tree): array {
          return $this->processTree($tree);
        },
      ],
    ];
    $tree = $this->menuLinkTree->transform($tree, $manipulators);

    $build = $this->menuLinkTree->build($tree);
    $build['#attributes']['class'] = ['toolbar-menu'];

    CacheableMetadata::createFromRenderArray($build)
      ->addCacheableDependency($this->config)
      ->applyTo($build);

    return $build;
  }

  /**
   * Adds toolbar-specific attributes to the menu link tree.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The menu link tree to manipulate.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   The manipulated menu link tree.
   */
  public function processTree(array $tree): array {
    $visible_items = $this->config->get('toolbar_items') ?: [];

    foreach ($tree as $element) {
      $plugin_id = $element->link->getPluginId();
      if (!in_array($plugin_id, $visible_items)) {
        // Add a class that allow to hide the non prioritized menu items when
        // the toolbar has horizontal orientation.
        $element->options['attributes']['class'][] = 'toolbar-horizontal-item-hidden';
      }
    }

    return $tree;
  }

}
