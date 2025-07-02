<?php

namespace Drupal\workbench_access\Plugin\AccessControlHierarchy;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeTypeInterface;
use Drupal\system\MenuInterface;
use Drupal\workbench_access\AccessControlHierarchyBase;
use Drupal\workbench_access\Entity\AccessSchemeInterface;
use Drupal\workbench_access\WorkbenchAccessManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a hierarchy based on a Menu.
 *
 * @AccessControlHierarchy(
 *   id = "menu",
 *   module = "menu_ui",
 *   entity = "menu_link_content",
 *   label = @Translation("Menu"),
 *   description = @Translation("Uses a menu as an access control hierarchy.")
 * )
 */
class Menu extends AccessControlHierarchyBase {

  use StringTranslationTrait;

  /**
   * Menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuTree;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var self $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    return $instance->setMenuTree($container->get('menu.link_tree'));
  }

  /**
   * Sets menu tree service.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menuTree
   *   Menu tree service.
   *
   * @return $this
   */
  public function setMenuTree(MenuLinkTreeInterface $menuTree) {
    $this->menuTree = $menuTree;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTree() {
    if (!isset($this->tree)) {
      $tree = [];
      $menuStorage = $this->entityTypeManager->getStorage('menu');
      foreach ($menuStorage->loadMultiple($this->configuration['menus']) as $menu_id => $menu) {
        $tree[$menu_id][$menu_id] = [
          'label' => $menu->label(),
          'depth' => 0,
          'parents' => [],
          'weight' => 0,
          'description' => $menu->label(),
          'path' => $menu->toUrl('edit-form')->toString(TRUE)->getGeneratedUrl(),
        ];
        $params = new MenuTreeParameters();
        $data = $this->menuTree->load($menu_id, $params);
        $this->tree = $this->buildTree($menu_id, $data, $tree);
      }
    }
    return $this->tree;
  }

  /**
   * Traverses the menu link tree and builds parentage arrays.
   *
   * Note: this method is necessary because Menu does not auto-load parents.
   *
   * @param string $id
   *   The root id of the section tree.
   * @param array $data
   *   An array of menu tree or subtree data.
   * @param array &$tree
   *   The computed tree array to return.
   *
   * @return array
   *   The compiled tree data.
   */
  protected function buildTree($id, array $data, array &$tree) {
    foreach ($data as $link_id => $link) {
      $tree[$id][$link_id] = [
        'id' => $link_id,
        'label' => $link->link->getTitle(),
        'depth' => $link->depth,
        'parents' => [],
        'weight' => $link->link->getWeight(),
        'description' => $link->link->getDescription(),
        'path' => $link->link->getUrlObject()->toString(TRUE)->getGeneratedUrl(),
      ];
      // Get the parents.
      if ($parent = $link->link->getParent()) {
        $tree[$id][$link_id]['parents'] = array_unique(array_merge($tree[$id][$link_id]['parents'], [$parent]));
        $tree[$id][$link_id]['parents'] = array_unique(array_merge($tree[$id][$link_id]['parents'], $tree[$id][$parent]['parents']));
      }
      else {
        $tree[$id][$link_id]['parents'] = [$id];
      }
      if (isset($link->subtree)) {
        // The elements of the 'subtree' sub-array are not sorted by weight.
        uasort($link->subtree, [$this, 'sortTree']);
        $this->buildTree($id, $link->subtree, $tree);
      }
    }
    return $tree;
  }

  /**
   * Sorts the menu tree by weight.
   */
  protected function sortTree($a, $b) {
    if ($a->link->getWeight() === $b->link->getWeight()) {
      return ($a->link->getTitle() > $b->link->getTitle()) ? 1 : 0;
    }
    return ($a->link->getWeight() > $b->link->getWeight()) ? 1 : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(AccessSchemeInterface $scheme, array &$form, FormStateInterface &$form_state, ContentEntityInterface $entity) {
    if (!isset($form['menu'])) {
      return;
    }
    $element = &$form['menu'];
    $menu_check = [];
    $user_sections = $this->userSectionStorage->getUserSections($scheme);

    // If the user cannot assign content to a menu, remove this option.
    if (empty(($user_sections))) {
      $element['#access'] = FALSE;
    }

    // Now restrict to available options. Note that if the default item
    // is not accessible, it is removed.
    foreach ($element['link']['menu_parent']['#options'] as $id => $data) {
      // The menu value here prepends the menu name. Remove that.
      $parts = explode(':', $id);
      $menu = array_shift($parts);
      // If the second element is empty, this is the root element which is
      // checked by menu name.
      if (empty($parts)) {
        $sections = [$menu];
      }
      else {
        $sections = [implode(':', $parts)];
      }
      $menu_parent = $menu . ':';

      // Remove unusable elements, except the existing parent.
      // Do not remove top-level menus, we check those separately.
      if (!empty($element['link']['menu_parent']['#options'][$id]) && $id != $menu_parent && empty(WorkbenchAccessManager::checkTree($scheme, $sections, $user_sections))) {
        unset($element['link']['menu_parent']['#options'][$id]);
      }
      // Check for the root menu item.
      if (!isset($menu_check[$menu]) && isset($element['link']['menu_parent']['#options'][$menu . ':'])) {
        if (empty(WorkbenchAccessManager::checkTree($scheme, [$menu], $user_sections))) {
          $base_menu = $element['link']['menu_parent']['#options'][$menu . ':'];
          unset($element['link']['menu_parent']['#options'][$menu . ':']);
        }
        $menu_check[$menu] = TRUE;
      }
    }
    // Fallback in case no options remains, we re-add the top-level item.
    // Ideally, we would never get here.
    if ($element['#access'] !== FALSE && empty($element['link']['menu_parent']['#options']) && isset($base_menu)) {
      $element['link']['menu_parent']['#options'][$menu . ':'] = $base_menu;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityValues(EntityInterface $entity) {
    $values = [];
    $defaults = menu_ui_get_menu_link_defaults($entity);
    if (!empty($defaults['id'])) {
      $values = [$defaults['id']];
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function disallowedOptions(array $field) {
    // On the menu form, we never remove an existing parent item, so there is
    // no concept of a disallowed option.
    return [];
  }

  /**
   * {@inheritdoc}
   *
   * @todo Refactor
   */
  public function getViewsJoin($entity_type, $key, $alias = NULL) {
    if ($entity_type === 'user') {
      $configuration['menu'] = [
        'table' => 'section_association__user_id',
        'field' => 'user_id_target_id',
        'left_table' => 'users',
        'left_field' => $key,
        'operator' => '=',
        'table_alias' => 'section_association__user_id',
        'real_field' => 'entity_id',
      ];
      return $configuration;
    }
    else {
      $configuration['menu'] = [
        'table' => 'menu_tree',
        'field' => 'route_param_key',
        'left_table' => 'node',
        'left_field' => $key,
        'left_query' => "CONCAT('node=', {$alias}.{$key})",
        'operator' => '=',
        'table_alias' => 'menu_tree',
        'real_field' => 'id',
      ];
    }
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function viewsData(array &$data, AccessSchemeInterface $scheme) {
    $data['node']['workbench_access_section'] = [
      'title' => $this->t('Workbench Section @name', ['@name' => $scheme->label()]),
      'help' => $this->t('The sections to which this content belongs in the @name scheme.', [
        '@name' => $scheme->label(),
      ]),
      'field' => [
        'scheme' => $scheme->id(),
        'id' => 'workbench_access_section',
      ],
      'filter' => [
        'scheme' => $scheme->id(),
        'field' => 'nid',
        'id' => 'workbench_access_section',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function applies($entity_type_id, $bundle) {
    return $entity_type_id === 'node' && in_array($bundle, $this->configuration['bundles']);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['menus'] = array_values(array_filter($form_state->getValue('menus')));
    $this->configuration['bundles'] = array_values(array_filter($form_state->getValue('bundles')));
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['menus'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Menus'),
      '#description' => $this->t('Select the menus to use.'),
      '#options' => array_map(function (MenuInterface $menu) {
        return $menu->label();
      }, $this->entityTypeManager->getStorage('menu')->loadMultiple()),
      '#default_value' => $this->configuration['menus'],
    ];
    $form['bundles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content types'),
      '#description' => $this->t('Select the content types to enable access control on.'),
      '#options' => array_map(function (NodeTypeInterface $node_type) {
        return $node_type->label();
      }, $this->entityTypeManager->getStorage('node_type')->loadMultiple()),
      '#default_value' => $this->configuration['bundles'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'menus' => [],
      'bundles' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $entity_type_map = [
      'menu' => 'menus',
      'node_type' => 'bundles',
    ];
    $dependencies = [];
    foreach ($entity_type_map as $entity_type => $configuration_key) {
      $dependencies = array_merge($dependencies, $this->entityTypeManager->getStorage($entity_type)->loadMultiple($this->configuration[$configuration_key]));
    }
    return array_reduce($dependencies, function (array $carry, ConfigEntityInterface $entity) {
      $carry[$entity->getConfigDependencyKey()][] = $entity->getConfigDependencyName();
      return $carry;
    }, []);
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $bundles = array_diff($this->configuration['bundles'], array_reduce($dependencies['config'], function (array $carry, $item) {
      if (!$item instanceof NodeTypeInterface) {
        return $carry;
      }
      $carry[] = $item->id();
      return $carry;
    }, []));
    $menus = array_diff($this->configuration['menus'], array_reduce($dependencies['config'], function (array $carry, $item) {
      if (!$item instanceof NodeTypeInterface) {
        return $carry;
      }
      $carry[] = $item->id();
      return $carry;
    }, []));
    $changed = ($menus != $this->configuration['menus']) || ($bundles != $this->configuration['bundles']);
    $this->configuration['menus'] = $menus;
    $this->configuration['bundles'] = $bundles;
    return $changed;
  }

}
