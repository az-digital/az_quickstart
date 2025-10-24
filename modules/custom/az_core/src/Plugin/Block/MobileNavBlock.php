<?php

namespace Drupal\az_core\Plugin\Block;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\Context\AccountPermissionsCacheContext;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Mobile Nav Block.
 */
#[Block(
  id: "mobile_nav_block",
  admin_label: new TranslatableMarkup("Mobile Nav Block"),
  category: new TranslatableMarkup("Menus")
)]

class MobileNavBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The custom ID to denote the root of the nav menu.
   *
   * @var string
   */
  protected const NAV_MENU_ROOT_ID = 'root';

  /**
   * The text for the root of the Main Navigation menu.
   *
   * @var string
   */
  protected const NAV_MENU_ROOT_TEXT = 'Main Menu';

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * The account permissions cache context service.
   *
   * @var \Drupal\Core\Cache\Context\AccountPermissionsCacheContext
   */
  protected $accountPermissionsContext;

  /**
   * Constructs a MobileNavBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend to use for menu storage.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_link_tree
   *   The menu link tree service.
   * @param \Drupal\Core\Cache\Context\AccountPermissionsCacheContext $account_permissions_context
   *   The account permissions cache context.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RouteMatchInterface $route_match,
    CacheBackendInterface $cache_backend,
    MenuLinkTreeInterface $menu_link_tree,
    AccountPermissionsCacheContext $account_permissions_context,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->cache = $cache_backend;
    $this->menuLinkTree = $menu_link_tree;
    $this->accountPermissionsContext = $account_permissions_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('cache.default'),
      $container->get('menu.link_tree'),
      $container->get('cache_context.user.permissions')
    );
  }

  /**
   * Load the main menu tree and save it to the cache.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   An array of MenuLinkTreeElements containing the full main menu tree.
   */
  protected function initMenuTree(): array {
    $userPermissionsContext = $this->accountPermissionsContext->getContext();
    $cachedTreeData = $this->cache->get('az_mobile_nav_menu.menu_tree:' . $userPermissionsContext);
    if ($cachedTreeData) {
      return $cachedTreeData->data;
    }
    $parameters = new MenuTreeParameters();
    // Skip disabled links in the menu.
    $parameters->onlyEnabledLinks();

    // Load the menu tree for the Main Navigation menu.
    /** @var \Drupal\Core\Menu\MenuLinkTreeElement[] $tree */
    $tree = $this->menuLinkTree->load('main', $parameters);

    // Apply manipulators.
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkNodeAccess'],
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuLinkTree->transform($tree, $manipulators);

    // Save the tree to the cache backend.
    $this->cache->set(
      'az_mobile_nav_menu.menu_tree:' . $userPermissionsContext,
      $tree,
      CacheBackendInterface::CACHE_PERMANENT,
      [
        'config:system.menu.main',
      ]);
    return $tree;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // On initial load, set the menu root as the current page (if possible).
    $menuRoot = $this->configuration['menu_root'] ?? FALSE;
    /** @var \Drupal\Core\Menu\MenuLinkTreeElement[] $tree */
    $tree = $this->initMenuTree();
    $treeWithText = [];
    if (!$menuRoot) {
      $treeWithText = $this->getSubtreeAndParentTextByRoute($tree, $this->routeMatch->getRouteName(), $this->routeMatch->getRawParameters()->all());
    }
    elseif (!empty($tree) && $menuRoot !== self::NAV_MENU_ROOT_ID) {
      $treeWithText = $this->getSubtreeAndParentText($tree, $menuRoot);
    }

    // Add library and cache properties.
    $build = [
      '#attached' => [
        'library' => [
          'az_core/az-mobile-nav',
        ],
      ],
      '#cache' => [
        'tags' => ['config:system.menu.main'],
        'contexts' => ['route'],
        'max-age' => CacheBackendInterface::CACHE_PERMANENT,
      ],
    ];

    // Initialize the main render array elements.
    $build['az_mobile_nav_menu'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => 'az_mobile_nav_menu',
      ],
    ];
    $build['az_mobile_nav_menu']['back'] = [];
    $build['az_mobile_nav_menu']['heading_div'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => [
          'border-bottom',
        ],
      ],
    ];
    $build['az_mobile_nav_menu']['menu_links'] = [
      '#type' => 'html_tag',
      '#tag' => 'ul',
      '#attributes' => [
        'id' => 'az_mobile_nav_menu_links',
        'class' => ['nav nav-pills flex-column bg-white'],
      ],
    ];

    // Initialize icon elements.
    $chevronLeft = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => 'chevron_left',
      '#attributes' => [
        'class' => [
          'material-symbols-rounded',
          'text-azurite',
        ],
      ],
    ];
    $chevronRight = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => 'chevron_right',
      '#attributes' => [
        'class' => [
          'material-symbols-rounded',
          'text-azurite',
        ],
      ],
    ];

    // Build the heading element and back link to the parent (if available).
    if (empty($treeWithText)) {
      $treeWithText = $tree;
      $isMainMenu = TRUE;
      $build['az_mobile_nav_menu']['heading_div']['heading'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => [
            'px-3',
            'fw-bold',
            'az-mobile-nav-root',
          ],
        ],
      ];
      $build['az_mobile_nav_menu']['heading_div']['heading']['heading_text'] = [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('@root', ['@root' => self::NAV_MENU_ROOT_TEXT]),
        '#attributes' => [
          'class' => ['h5 my-0'],
        ],
      ];
    }
    else {
      $isMainMenu = FALSE;
      $rootElement = $treeWithText[array_key_first($treeWithText)];
      $parent = $rootElement->link->getParent();
      $title = [$chevronLeft];
      $title[] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $parent === '' ? $this->t('Back to Main Menu') : $this->t('Back to @parentText', ['@parentText' => $treeWithText['parentText']]),
      ];
      $build['az_mobile_nav_menu']['back'] = [
        '#type' => 'link',
        '#name' => $parent,
        '#title' => $title,
        '#url' => Url::fromRoute(
          'az_core.mobile_nav_callback',
          [
            'menu_root' => $parent === '' ? $this->t('@root', ['@root' => self::NAV_MENU_ROOT_TEXT]) : $parent,
          ],
        ),
        '#attributes' => [
          'type' => 'button',
          'class' => [
            'use-ajax',
            'ps-0',
            'pe-3',
            'mb-1',
            'text-azurite',
            'az-mobile-nav-back',
          ],
          'data-ajax-http-method' => 'GET',
        ],
      ];
      if ($rootElement->link->getRouteName() === '<button>' || $rootElement->link->getRouteName() === '<nolink>') {
        $build['az_mobile_nav_menu']['heading_div']['heading'] = [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => [
              'px-3',
              'fw-bold',
              'az-mobile-nav-root',
            ],
          ],
        ];
        $build['az_mobile_nav_menu']['heading_div']['heading']['heading_text'] = [
          '#type' => 'html_tag',
          '#tag' => 'h2',
          '#value' => $rootElement->link->getTitle(),
          '#attributes' => [
            'class' => ['h5 my-0'],
          ],
        ];
      }
      else {
        $pageLinkTitle = [
          '#type' => 'html_tag',
          '#tag' => 'h2',
          '#value' => $rootElement->link->getTitle(),
          '#attributes' => [
            'class' => ['h5 my-0'],
          ],
        ];
        $build['az_mobile_nav_menu']['heading_div']['heading'] = [
          '#type' => 'link',
          '#attributes' => [
            'role' => 'button',
            'class' => [
              'px-3',
              'text-blue',
              'az-mobile-nav-root',
            ],
          ],
          '#title' => $pageLinkTitle,
          '#url' => $rootElement->link->getUrlObject(),
        ];
      }

      $treeWithText = $rootElement->subtree;
    }

    // Build the list of menu links.
    foreach ($treeWithText as $item) {
      // Do not display menu links to pages inaccessible to the current user.
      if ($item->access === NULL || !$item->access instanceof AccessResultInterface || !$item->access->isAllowed()) {
        continue;
      }
      if ($item->link->getRouteName() === '<button>' || $item->link->getRouteName() === '<nolink>') {
        $pageLink = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $item->link->getTitle(),
          '#attributes' => [
            'class' => [
              $isMainMenu ? 'ms-2' : 'ms-3',
              'text-black',
              'border-end',
            ],
          ],
        ];
      }
      else {
        $pageLink = [
          '#type' => 'link',
          '#attributes' => [
            'role' => 'button',
            'class' => [
              'nav-link',
              $isMainMenu ? 'ms-2' : 'ms-3',
            ],
          ],
          '#title' => $item->link->getTitle(),
          '#url' => $item->link->getUrlObject(),
        ];
      }

      $childrenLink = [];
      if ($item->hasChildren) {
        $pageLink['#attributes']['class'][] = 'border-end';
        $childrenLink = [
          '#type' => 'link',
          '#name' => $item->link->getPluginId(),
          '#title' => $chevronRight,
          '#url' => Url::fromRoute('az_core.mobile_nav_callback',
            [
              'menu_root' => $item->link->getPluginId(),
            ],
          ),
          '#attributes' => [
            'type' => 'button',
            'class' => [
              'use-ajax',
              'btn',
              'btn-lg',
              'bg-white',
              'az-mobile-nav-link',
            ],
            'data-ajax-http-method' => 'GET',
            'aria-label' => $this->t('View child pages of @itemTitle', ['@itemTitle' => $item->link->getTitle()]),
          ],
        ];
      }
      $build['az_mobile_nav_menu']['menu_links'][$item->link->getPluginId()] = [
        '#type' => 'html_tag',
        '#tag' => 'li',
        '#attributes' => [
          'class' => ['nav-item'],
        ],
        'children' => !empty($childrenLink) ? [
          $pageLink,
          $childrenLink,
        ] : [$pageLink],
      ];
    }

    return $build;
  }

  /**
   * Get the subtree for the menu link matching the given plugin ID.
   *
   * @param array $tree
   *   The menu link tree.
   * @param string $pluginId
   *   The plugin ID of the menu link to be the root of the subtree.
   * @param bool $root
   *   True if the current menu link element is at the root of the given tree.
   *
   * @return array
   *   An array with the subtree and parent menu link text.
   */
  protected function getSubtreeAndParentText(array $tree, string $pluginId, bool $root = TRUE) {
    foreach ($tree as $menuLinkTreeElement) {
      if ($root && $menuLinkTreeElement->link->getPluginId() === $pluginId) {
        return [$menuLinkTreeElement, 'parentText' => $this->t('@root', ['@root' => self::NAV_MENU_ROOT_TEXT])];
      }
      elseif ($menuLinkTreeElement->link->getPluginId() === $pluginId) {
        return [$menuLinkTreeElement, 'parentText' => $menuLinkTreeElement->link->getParent()];
      }
      elseif ($menuLinkTreeElement->hasChildren) {
        $subtreeFound = $this->getSubtreeAndParentText($menuLinkTreeElement->subtree, $pluginId, FALSE);
        if (!empty($subtreeFound)) {
          if ($subtreeFound['parentText'] === $menuLinkTreeElement->link->getPluginId()) {
            $subtreeFound['parentText'] = $menuLinkTreeElement->link->getTitle();
          }
          return $subtreeFound;
        }
      }
    }
    return [];
  }

  /**
   * Get the subtree for the menu link matching the given route.
   *
   * @param array $tree
   *   The menu link tree.
   * @param string $routeName
   *   The route name of the menu link to be the root of the subtree.
   * @param array $routeParameters
   *   The route parameters of the menu link to be the root of the subtree.
   * @param bool $root
   *   True if the current menu link element is at the root of the given tree.
   *
   * @return array
   *   An array with the subtree and parent menu link text.
   */
  protected function getSubtreeAndParentTextByRoute(array $tree, string $routeName, array $routeParameters, bool $root = TRUE) {
    foreach ($tree as $menuLinkTreeElement) {
      if ($root && $menuLinkTreeElement->link->getRouteName() === $routeName && $menuLinkTreeElement->link->getRouteParameters() === $routeParameters) {
        if ($menuLinkTreeElement->hasChildren) {
          return [$menuLinkTreeElement, 'parentText' => $this->t('@root', ['@root' => self::NAV_MENU_ROOT_TEXT])];
        }
        else {
          // Leaf on the main menu: don't return a subtree.
          return [];
        }
      }
      elseif ($menuLinkTreeElement->link->getRouteName() === $routeName && $menuLinkTreeElement->link->getRouteParameters() === $routeParameters) {
        if ($menuLinkTreeElement->hasChildren) {
          return [$menuLinkTreeElement, 'parentText' => $menuLinkTreeElement->link->getParent()];
        }
        else {
          // Leaf menu item: use its parent as the root instead.
          return [[], 'parentText' => 'use parent as root'];
        }
      }
      elseif ($menuLinkTreeElement->hasChildren) {
        $subtreeFound = $this->getSubtreeAndParentTextByRoute($menuLinkTreeElement->subtree, $routeName, $routeParameters, FALSE);
        if (!empty($subtreeFound)) {
          if ($subtreeFound['parentText'] === 'use parent as root') {
            return [$menuLinkTreeElement, 'parentText' => $menuLinkTreeElement->link->getParent()];
          }
          elseif ($subtreeFound['parentText'] === $menuLinkTreeElement->link->getPluginId()) {
            $subtreeFound['parentText'] = $menuLinkTreeElement->link->getTitle();
          }
          return $subtreeFound;
        }
      }
    }
    return [];
  }

}
