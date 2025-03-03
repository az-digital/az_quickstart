<?php

namespace Drupal\az_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Mobile Nav form.
 */
class MobileNavForm extends FormBase {

  /**
   * The time to live for the menu tree in key/value storage, in seconds.
   *
   * @var int
   */
  protected const EXPIRE_TIME = 900;

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
   * Constructs a new Mobile Nav Form.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match object.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface $key_value_expirable
   *   The key value expirable factory.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menuTree
   *   The menu link tree service.
   */
  public function __construct(RouteMatchInterface $routeMatch, KeyValueStoreExpirableInterface $key_value_expirable, MenuLinkTreeInterface $menuTree) {
    $this->routeMatch = $routeMatch;
    $this->menuTreeStore = $key_value_expirable;
    $treeFromStorage = $this->menuTreeStore->get('menu');
    if (empty($treeFromStorage)) {
      $this->tree = $this->initMenuTree($menuTree);
    }
    else {
      $this->tree = $treeFromStorage;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('keyvalue.expirable')->get('az_core.az_mobile_nav'),
      $container->get('menu.link_tree')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mobile_nav_form';
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
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Initialize the main form elements.
    $form['az_mobile_nav_menu'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => 'az_mobile_nav_menu',
        'class' => ['mx-2'],
      ],
    ];
    $form['az_mobile_nav_menu']['back'] = [];
    $form['az_mobile_nav_menu']['heading'] = [
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
    $form['az_mobile_nav_menu']['menu_links'] = [
      '#type' => 'html_tag',
      '#tag' => 'ul',
      '#attributes' => [
        'id' => 'menu_links',
        'class' => ['nav nav-pills flex-column bg-white'],
      ],
    ];

    // Get the button the user clicked on (if available).
    $trigger = $form_state->getTriggeringElement();

    // On initial load, set the menu root as the current page (if possible).
    if (!isset($trigger) && !$form_state->isRebuilding()) {
      $tree = $this->getSubtreeAndParentTextByRoute($this->tree, $this->routeMatch->getRouteName(), $this->routeMatch->getRawParameters()->all());
    }
    elseif ($trigger && !empty($this->tree) && $trigger['#name']) {
      $tree = $this->getSubtreeAndParentText($this->tree, $trigger['#name']);
    }

    // Build the heading links to the parent and current root (if available).
    if (empty($tree)) {
      $tree = $this->tree;
      $isMainMenu = TRUE;
      $form['az_mobile_nav_menu']['heading']['#attributes']['class'][] = 'pl-3';
      $form['az_mobile_nav_menu']['heading']['selected_page'] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => 'Main Menu',
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
      $form['az_mobile_nav_menu']['heading']['#attributes']['class'][] = 'pl-4';
      $rootElement = $tree[array_key_first($tree)];
      $parent = $rootElement->link->getParent();
      $form['az_mobile_nav_menu']['back'] = [
        '#type' => 'submit',
        '#form_id' => $this->getFormId(),
        '#name' => $parent,
        '#value' => $parent === '' ? 'Back to Main Menu' : 'Back to ' . $tree['parentText'],
        '#attributes' => [
          'type' => 'button',
          'class' => [
            'az-mobile-nav-previous',
            'border-0',
            'bg-transparent',
            'pl-0',
            'pr-3',
            'py-1',
            'mb-1',
            'text-azurite',
            'text-left',
            'text-decoration-none',
          ],
        ],
        '#ajax' => [
          'wrapper' => 'az_mobile_nav_menu',
          'callback' => '::formCallback',
          'progress' => [],
          'disable-refocus' => TRUE,
        ],
      ];
      if ($rootElement->link->getRouteName() === '<button>') {
        $form['az_mobile_nav_menu']['heading']['selected_page'] = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $rootElement->link->getTitle(),
          '#attributes' => [
            'class' => ['d-inline-block py-1'],
          ],
        ];
      }
      else {
        $form['az_mobile_nav_menu']['heading']['selected_page'] = [
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
          $form['az_mobile_nav_menu']['heading']['#attributes']['class'][] = 'az-mobile-nav-current';
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
          '#type' => 'submit',
          '#form_id' => $this->getFormId(),
          '#name' => $item->link->getPluginId(),
          '#value' => $item->link->getTitle(),
          '#attributes' => [
            'type' => 'button',
            'class' => [
              'btn',
              'btn-lg',
              'py-0',
              'border-left',
            ],
          ],
          '#ajax' => [
            'wrapper' => 'az_mobile_nav_menu',
            'callback' => '::formCallback',
            'progress' => [],
            'disable-refocus' => TRUE,
          ],
        ];
      }
      $form['az_mobile_nav_menu']['menu_links'][$item->link->getPluginId()] = [
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

    return $form;
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
        return [$menuLinkTreeElement, 'parentText' => 'Main Menu'];
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
        return [$menuLinkTreeElement, 'parentText' => 'Main Menu'];
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

  /**
   * Callback for the form's button elements.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function formCallback(array $form, FormStateInterface &$form_state) {
    return $form['az_mobile_nav_menu'];
  }

  /**
   * Submit handler required by FormInterface.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
  }

}
