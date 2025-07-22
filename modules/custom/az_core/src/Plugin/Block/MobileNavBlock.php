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
  protected const MENU_ROOT_ID = 'root';

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
   * The menu link ID for the root of the nav menu.
   *
   * @var string
   */
  protected $menuRoot;

  /**
   * Constructs a MobileNavBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match object.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface $key_value_expirable
   *   The key value expirable factory.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menuTree
   *   The menu link tree service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $routeMatch, KeyValueStoreExpirableInterface $key_value_expirable, MenuLinkTreeInterface $menuTree) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if (isset($configuration['menu_root'])) {
      $this->menuRoot = $configuration['menu_root'];
    }
    $this->routeMatch = $routeMatch;
    $this->menuTreeStore = $key_value_expirable;
    $treeFromStorage = $this->menuTreeStore->get('menu');
    if (is_array($treeFromStorage) && count($treeFromStorage) > 1) {
      $this->tree = $treeFromStorage;
    }
    else {
      // Refresh menu tree if it's empty or only contains the front page.
      $this->tree = $this->initMenuTree($menuTree);
    }
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
  protected function initMenuTree(MenuLinkTreeInterface $menuTree) {
    $parameters = new MenuTreeParameters();
    // Skip disabled links in the menu.
    $parameters->onlyEnabledLinks();

    // Load the menu tree.
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
    ];
    $build['az_mobile_nav_menu']['back'] = [];
    $build['az_mobile_nav_menu']['heading'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#attributes' => [
        'class' => [
          'h5',
          'py-1',
          'mt-1',
          'mb-0',
          'bg-gray-200',
          'text-black',
          'border-bottom',
        ],
      ],
    ];
    $build['az_mobile_nav_menu']['menu_links'] = [
      '#type' => 'html_tag',
      '#tag' => 'ul',
      '#attributes' => [
        'id' => 'menu_links',
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
          'material-icons-sharp',
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
          'material-icons-sharp',
          'text-azurite',
          'align-middle',
        ],
      ],
    ];

    // On initial load, set the menu root as the current page (if possible).
    if (!isset($this->menuRoot)) {
      $tree = $this->getSubtreeAndParentTextByRoute($this->tree, $this->routeMatch->getRouteName(), $this->routeMatch->getRawParameters()->all());
    }
    elseif ($this->menuRoot && !empty($this->tree) && $this->menuRoot !== self::MENU_ROOT_ID) {
      $tree = $this->getSubtreeAndParentText($this->tree, $this->menuRoot);
    }

    // Build the heading links to the parent and current root (if available).
    if (empty($tree)) {
      $tree = $this->tree;
      $isMainMenu = TRUE;
      $build['az_mobile_nav_menu']['heading']['#attributes']['class'][] = 'pl-3';
      $build['az_mobile_nav_menu']['heading']['selected_page'] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $this->t('Main Menu'),
        '#attributes' => [
          'class' => [
            'd-inline-block',
            'py-1',
          ],
        ],
      ];
    }
    else {
      $isMainMenu = FALSE;
      $build['az_mobile_nav_menu']['heading']['#attributes']['class'][] = 'pl-4';
      $rootElement = $tree[array_key_first($tree)];
      $parent = $rootElement->link->getParent();
      $title = $chevronLeft;
      $parent === '' ?
        $title['#suffix'] = $this->t('Back to Main Menu') :
        $title['#suffix'] = $this->t('Back to @parentText', ['@parentText' => $tree['parentText']]);
      $build['az_mobile_nav_menu']['back'] = [
        '#type' => 'link',
        '#name' => $parent,
        '#title' => $title,
        '#url' => Url::fromRoute('az_core.mobile_nav_callback',
          [
            'menu_root' => $parent === '' ? self::MENU_ROOT_ID : $parent,
          ],
        ),
        '#attributes' => [
          'type' => 'button',
          'class' => [
            'use-ajax',
            'border-0',
            'bg-transparent',
            'pl-0',
            'pr-3',
            'py-1',
            'mb-1',
            'font-weight-normal',
            'text-azurite',
            'text-left',
            'text-decoration-none',
          ],
        ],
      ];
      if ($rootElement->link->getRouteName() === '<button>') {
        $build['az_mobile_nav_menu']['heading']['selected_page'] = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $rootElement->link->getTitle(),
          '#attributes' => [
            'class' => ['d-inline-block py-1'],
          ],
        ];
      }
      else {
        $build['az_mobile_nav_menu']['heading']['selected_page'] = [
          '#type' => 'link',
          '#attributes' => [
            'role' => 'button',
            'class' => [
              'd-inline-block',
              'py-1',
              'text-blue',
              'text-left',
              'text-decoration-none',
            ],
          ],
          '#title' => $rootElement->link->getTitle(),
          '#url' => $rootElement->link->getUrlObject(),
        ];
        if ($rootElement->link->getRouteName() === $this->routeMatch->getRouteName() && $rootElement->link->getRouteParameters() === $this->routeMatch->getRawParameters()->all()) {
          $build['az_mobile_nav_menu']['heading']['#attributes']['class'][] = 'az-mobile-nav-current';
        }
      }

      $tree = $rootElement->subtree;
    }

    // Build the list of menu links.
    foreach ($tree as $item) {
      if ($item->link->getRouteName() === '<button>') {
        $pageLink = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $item->link->getTitle(),
          '#attributes' => [
            'class' => [
              'nav-link',
              $isMainMenu ? 'ml-2' : 'ml-3',
              'text-black',
              'text-left',
              'flex-grow-1',
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
              $isMainMenu ? 'ml-2' : 'ml-3',
              'text-azurite',
              'text-left',
              'flex-grow-1',
            ],
          ],
          '#title' => $item->link->getTitle(),
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
            ],
          ),
          '#attributes' => [
            'type' => 'button',
            'class' => [
              'use-ajax',
              'az-mobile-nav-link',
              'btn',
              'btn-lg',
              'py-0',
              'border-left',
            ],
            'aria-label' => $this->t('View child pages of @itemTitle', ['@itemTitle' => $item->link->getTitle()]),
          ],
        ];
      }
      $build['az_mobile_nav_menu']['menu_links'][$item->link->getPluginId()] = [
        '#type' => 'html_tag',
        '#tag' => 'li',
        '#attributes' => [
          'class' => [
            'nav-item',
            'd-flex',
          ],
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
        return [$menuLinkTreeElement, 'parentText' => $this->t('Main Menu')];
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
        return [$menuLinkTreeElement, 'parentText' => $this->t('Main Menu')];
      }
      elseif ($menuLinkTreeElement->link->getRouteName() === $routeName && $menuLinkTreeElement->link->getRouteParameters() === $routeParameters) {
        return [$menuLinkTreeElement, 'parentText' => $menuLinkTreeElement->link->getParent()];
      }
      elseif ($menuLinkTreeElement->hasChildren) {
        $subtreeFound = $this->getSubtreeAndParentTextByRoute($menuLinkTreeElement->subtree, $routeName, $routeParameters, FALSE);
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

}
