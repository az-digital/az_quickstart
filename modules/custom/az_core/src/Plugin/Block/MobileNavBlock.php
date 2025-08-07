<?php

namespace Drupal\az_core\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
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
   * The time to live for the menu tree in key/value storage, in seconds.
   *
   * @var int
   */
  protected const EXPIRE_TIME = 900;

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
  protected $navMenuRootText;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The key/value store for the menu tree.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $menuTreeStore;

  /**
   * The menu link tree array.
   *
   * @var array
   */
  protected $tree;

  /**
   * The menu link ID for the current page.
   *
   * @var string
   */
  protected $currentPage;

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
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface $key_value_expirable
   *   The key value expirable factory.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_tree
   *   The menu link tree service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, KeyValueStoreExpirableInterface $key_value_expirable, MenuLinkTreeInterface $menu_tree) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentPage = $configuration['current_page'] ?? 'none';
    $this->routeMatch = $route_match;
    $this->menuTreeStore = $key_value_expirable;
    $treeFromStorage = $this->menuTreeStore->get('menu');
    if (is_array($treeFromStorage) && count($treeFromStorage) > 1) {
      $this->tree = $treeFromStorage;
    }
    else {
      // Refresh menu tree if it's empty or only contains the front page.
      $this->tree = $this->initMenuTree($menu_tree);
    }

    // Set default "Main Menu" text.
    $this->navMenuRootText = $this->t('Main Menu');
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
      $container->get('keyvalue.expirable')->get('az_core.az_mobile_nav_new'),
      $container->get('menu.link_tree')
    );
  }

  /**
   * Load the main menu tree and save it to key/value storage.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menuTree
   *   The menu link tree service.
   *
   * @return array
   *   An array containing the full main menu tree.
   */
  protected function initMenuTree(MenuLinkTreeInterface $menuTree): array {
    $parameters = new MenuTreeParameters();
    // Skip disabled links in the menu.
    $parameters->onlyEnabledLinks();

    // Load the menu tree for the Main Navigation menu.
    $tree = $menuTree->load('main', $parameters);

    // Apply manipulators.
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $menuTree->transform($tree, $manipulators);

    // Save the tree to key value storage.
    $this->menuTreeStore->setWithExpire('menu', $tree, self::EXPIRE_TIME);
    return $tree;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Initialize the main render array elements.
    $build['az_mobile_nav_menu'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => 'az_mobile_nav_menu',
        'class' => ['mx-2'],
      ],
      '#attached' => [
        'library' => [
          'az_core/az-mobile-nav',
        ],
      ],
      // @todo Temporary workaround to prevent block not updating after
      // navigating to a new page.
      '#cache' => [
        'max-age' => 0,
      ],
    ];
    $build['az_mobile_nav_menu']['back'] = [];
    $build['az_mobile_nav_menu']['heading_div'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => [
          'border-bottom',
          'overflow-hidden',
        ],
      ],
    ];
    $build['az_mobile_nav_menu']['heading_div']['heading'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#attributes' => [
        'class' => [
          'h5',
          'py-1',
          'my-0',
        ],
      ],
    ];
    $build['az_mobile_nav_menu']['menu_links'] = [
      '#type' => 'html_tag',
      '#tag' => 'ul',
      '#attributes' => [
        'id' => 'menu_links',
        'class' => ['nav nav-pills flex-column bg-white mb-2'],
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
          'align-top',
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
          'align-bottom',
        ],
      ],
    ];

    // On initial load, set the menu root as the current page (if possible).
    $menuRoot = $this->configuration['menu_root'] ?? FALSE;
    $treeWithText = [];
    if (!$menuRoot) {
      $treeWithText = $this->getSubtreeAndParentTextByRoute($this->tree, $this->routeMatch->getRouteName(), $this->routeMatch->getRawParameters()->all());
    }
    else {
      if ($menuRoot && !empty($this->tree) && $menuRoot !== self::NAV_MENU_ROOT_ID) {
        $treeWithText = $this->getSubtreeAndParentText($this->tree, $menuRoot);
      }
    }

    // Build the heading links to the parent and current root (if available).
    if (empty($treeWithText)) {
      $treeWithText = $this->tree;
      $isMainMenu = TRUE;
      $build['az_mobile_nav_menu']['heading_div']['heading']['#attributes']['class'][] = 'ps-3';
      $build['az_mobile_nav_menu']['heading_div']['heading']['selected_page'] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $this->navMenuRootText,
        '#attributes' => [
          'class' => [
            'd-inline-block',
            'py-1',
            'fw-bold',
            'az-mobile-nav-root',
          ],
        ],
      ];
    }
    else {
      $isMainMenu = FALSE;
      $build['az_mobile_nav_menu']['heading_div']['heading']['#attributes']['class'][] = 'ps-3';
      $rootElement = $treeWithText[array_key_first($treeWithText)];
      $parent = $rootElement->link->getParent();
      $title = [$chevronLeft];
      $title[] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $parent === '' ? $this->t('Back to Main Menu') : $this->t('Back to @parentText', ['@parentText' => $treeWithText['parentText']]),
        '#attributes' => [
          'class' => ['ms-n1 align-text-top'],
        ],
      ];
      $build['az_mobile_nav_menu']['back'] = [
        '#type' => 'link',
        '#name' => $parent,
        '#title' => $title,
        '#url' => Url::fromRoute(
          'az_core.mobile_nav_callback',
          [
            'menu_root' => $parent === '' ? self::NAV_MENU_ROOT_ID : $parent,
            'current_page' => $this->currentPage,
          ],
        ),
        '#attributes' => [
          'type' => 'button',
          'class' => [
            'use-ajax',
            'ps-0',
            'pe-3',
            'py-1',
            'ms-n1',
            'mb-1',
            'text-azurite',
            'text-decoration-none',
          ],
        ],
      ];
      if ($rootElement->link->getRouteName() === '<button>') {
        $build['az_mobile_nav_menu']['heading_div']['heading']['selected_page'] = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $rootElement->link->getTitle(),
          '#attributes' => [
            'class' => ['d-inline-block py-1 fw-bold az-mobile-nav-root'],
          ],
        ];
      }
      else {
        $pageLinkTitle = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $rootElement->link->getTitle(),
          '#attributes' => [
            'class' => ['align-text-bottom'],
          ],
        ];
        $build['az_mobile_nav_menu']['heading_div']['heading']['selected_page'] = [
          '#type' => 'link',
          '#attributes' => [
            'role' => 'button',
            'class' => [
              'd-inline-block',
              'py-1',
              'text-blue',
              'text-decoration-none',
              'az-mobile-nav-root',
            ],
          ],
          '#title' => $pageLinkTitle,
          '#url' => $rootElement->link->getUrlObject(),
        ];
        if ($rootElement->link->getPluginId() === $this->currentPage) {
          $build['az_mobile_nav_menu']['heading_div']['heading']['#attributes']['class'][] = 'text-bg-gray-200 az-mobile-nav-current';
        }
      }

      $treeWithText = $rootElement->subtree;
    }

    // Build the list of menu links.
    foreach ($treeWithText as $item) {
      if ($item->link->getRouteName() === '<button>') {
        $pageLink = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $item->link->getTitle(),
          '#attributes' => [
            'class' => [
              'nav-link',
              $isMainMenu ? 'ms-2' : 'ms-3',
              'fw-normal',
              'text-black',
              'flex-grow-1',
            ],
          ],
        ];
      }
      else {
        $pageLinkTitle = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $item->link->getTitle(),
          '#attributes' => [
            'class' => ['align-text-top'],
          ],
        ];
        $pageLink = [
          '#type' => 'link',
          '#attributes' => [
            'role' => 'button',
            'class' => [
              'nav-link',
              $isMainMenu ? 'ms-2' : 'ms-3',
              'text-azurite',
              'flex-grow-1',
            ],
          ],
          '#title' => $pageLinkTitle,
          '#url' => $item->link->getUrlObject(),
        ];
      }

      $childrenLink = [];
      if ($item->hasChildren) {
        $childrenLink = [
          '#type' => 'link',
          '#name' => $item->link->getPluginId(),
          '#title' => $chevronRight,
          '#url' => Url::fromRoute('az_core.mobile_nav_callback',
            [
              'menu_root' => $item->link->getPluginId(),
              'current_page' => $this->currentPage,
            ],
          ),
          '#attributes' => [
            'type' => 'button',
            'class' => [
              'use-ajax',
              'btn',
              'btn-lg',
              'bg-white',
              'py-0',
              'border-start',
              'rounded-0',
              'az-mobile-nav-link',
            ],
            'aria-label' => $this->t('View child pages of @itemTitle', ['@itemTitle' => $item->link->getTitle()]),
          ],
        ];
      }
      $build['az_mobile_nav_menu']['menu_links'][$item->link->getPluginId()] = [
        '#type' => 'html_tag',
        '#tag' => 'li',
        '#attributes' => [
          'class' => ($item->link->getPluginId() === $this->currentPage) ?
            ['nav-item d-flex border-start-0 border-end-0 text-bg-gray-200 az-mobile-nav-current'] :
            ['nav-item d-flex border-start-0 border-end-0'],
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
        return [$menuLinkTreeElement, 'parentText' => $this->navMenuRootText];
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
        // Save the current page ID for subsequent AJAX requests.
        $this->currentPage = $menuLinkTreeElement->link->getPluginId();
        if ($menuLinkTreeElement->hasChildren) {
          return [$menuLinkTreeElement, 'parentText' => $this->navMenuRootText];
        }
        else {
          // Leaf on the main menu: don't return a subtree.
          return [];
        }
      }
      elseif ($menuLinkTreeElement->link->getRouteName() === $routeName && $menuLinkTreeElement->link->getRouteParameters() === $routeParameters) {
        // Save the current page ID for subsequent AJAX requests.
        $this->currentPage = $menuLinkTreeElement->link->getPluginId();
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
