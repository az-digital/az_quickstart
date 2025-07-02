<?php

namespace Drupal\menu_block\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;
use Drupal\system\Entity\Menu;
use Drupal\system\Plugin\Block\SystemMenuBlock;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an extended Menu block.
 *
 * @Block(
 *   id = "menu_block",
 *   admin_label = @Translation("Menu block"),
 *   category = @Translation("Menus"),
 *   deriver = "Drupal\menu_block\Plugin\Derivative\MenuBlock",
 *   forms = {
 *     "settings_tray" = "\Drupal\system\Form\SystemMenuOffCanvasForm",
 *   },
 * )
 */
class MenuBlock extends SystemMenuBlock {

  /**
   * Constant definition options for block label type.
   */
  const LABEL_BLOCK = 'block';
  const LABEL_MENU = 'menu';
  const LABEL_ACTIVE_ITEM = 'active_item';
  const LABEL_PARENT = 'parent';
  const LABEL_ROOT = 'root';
  const LABEL_FIXED = 'fixed';

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The menu parent form selector service.
   *
   * @var \Drupal\Core\Menu\MenuParentFormSelectorInterface
   */
  protected $menuParentFormSelector;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->menuParentFormSelector = $container->get('menu.parent_form_selector');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->configuration;
    $defaults = $this->defaultConfiguration();

    $form = parent::blockForm($form, $form_state);
    $menu_parent_selector = \Drupal::service('menu.parent_form_selector');

    // If there exists a config value for Expand all menu links (expand), that
    // value should populate core's Expand all menu items checkbox
    // (expand_all_items).
    if (isset($config['expand'])) {
      $form['menu_levels']['expand_all_items']['#default_value'] = $config['expand'];
    }

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced options'),
      '#open' => FALSE,
      '#process' => [[self::class, 'processMenuBlockFieldSets']],
    ];

    $menu_name = $this->getDerivativeId();
    $menus = Menu::loadMultiple([$menu_name]);
    $menus[$menu_name] = $menus[$menu_name]->label();

    $form['advanced']['parent'] = $menu_parent_selector->parentSelectElement($config['parent'], '', $menus);

    $form['advanced']['parent'] += [
      '#title' => $this->t('Fixed parent item'),
      '#description' => $this->t('Alter the options in “Menu levels” to be relative to the fixed parent item. The block will only contain children of the selected menu link.'),
    ];

    $form['advanced']['render_parent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<strong>Render parent item</strong>'),
      '#default_value' => $config['render_parent'],
      '#description' => $this->t('If the <strong>Initial visibility level</strong> is greater than 1, or a <strong>Fixed parent item</strong> is chosen, only the children of that item will be displayed by default. Enable this option to <strong>always</strong> render the parent item in the menu.'),
      '#states' => [
        'visible' => [
          ':input[name="settings[follow]"]' => ['unchecked' => TRUE],
          [
            [':input[name="settings[level]"]' => ['!value' => 1]],
            'or',
            [':input[name="settings[parent]"]' => ['!value' => $this->getDerivativeId() . ':']],
          ],
        ],
        // Ideally, we would uncheck the setting when it's not visible, but that
        // won't work until https://www.drupal.org/project/drupal/issues/994360
        // lands.
      ],
    ];

    $form['advanced']['label_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Use as title'),
      '#description' => $this->t('Replace the block title with an item from the menu.'),
      '#options' => [
        self::LABEL_BLOCK => $this->t('Block title'),
        self::LABEL_MENU => $this->t('Menu title'),
        self::LABEL_FIXED => $this->t("Fixed parent item's title"),
        self::LABEL_ACTIVE_ITEM => $this->t("Active item's title"),
        self::LABEL_PARENT => $this->t("Active trail's parent title"),
        self::LABEL_ROOT => $this->t("Active trail's root title"),
      ],
      '#default_value' => $config['label_type'],
      '#states' => [
        'visible' => [
          ':input[name="settings[label_display]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['advanced']['label_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link the title?'),
      '#default_value' => $config['label_link'],
      '#states' => [
        'visible' => [
          ':input[name="settings[label_display]"]' => ['checked' => TRUE],
          ':input[name="settings[label_type]"]' => [
            ['value' => self::LABEL_ACTIVE_ITEM],
            ['value' => self::LABEL_PARENT],
            ['value' => self::LABEL_ROOT],
            ['value' => self::LABEL_FIXED],
          ],
        ],
      ],
    ];

    $form['advanced']['hide_on_nonactive'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<strong>Hide on pages not included in menu</strong>'),
      '#default_value' => $config['hide_on_nonactive'],
      '#description' => $this->t('If checked, this block will not appear on any pages that are not linked in the menu. If unchecked, this block will appear on all pages. This option is also affected by visibility settings below.'),
    ];

    $form['style'] = [
      '#type' => 'details',
      '#title' => $this->t('HTML and style options'),
      '#open' => FALSE,
      '#process' => [[self::class, 'processMenuBlockFieldSets']],
    ];

    $form['advanced']['follow'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<strong>Make the initial visibility level follow the active menu item.</strong>'),
      '#default_value' => $config['follow'],
      '#description' => $this->t('If the active menu item is deeper than the initial visibility level set above, the initial visibility level will be relative to the active menu item. Otherwise, the initial visibility level of the tree will remain fixed.'),
    ];

    $form['advanced']['follow_parent'] = [
      '#type' => 'radios',
      '#title' => $this->t('Initial visibility level will be'),
      '#description' => $this->t('When following the active menu item, select whether the initial visibility level should be set to the active menu item, or its children.'),
      '#default_value' => $config['follow_parent'],
      '#options' => [
        'active' => $this->t('Active menu item'),
        'child' => $this->t('Children of active menu item'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="settings[follow]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['style']['suggestion'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Theme hook suggestion'),
      '#default_value' => $config['suggestion'],
      '#field_prefix' => '<code>menu__</code>',
      '#description' => $this->t('A theme hook suggestion can be used to override the default HTML and CSS classes for menus found in <code>menu.html.twig</code>.'),
      '#machine_name' => [
        'error' => $this->t('The theme hook suggestion must contain only lowercase letters, numbers, and underscores.'),
        'exists' => [$this, 'suggestionExists'],
      ],
    ];

    // Open the details field sets if their config is not set to defaults.
    foreach (['menu_levels', 'advanced', 'style'] as $fieldSet) {
      foreach (array_keys($form[$fieldSet]) as $field) {
        if (isset($defaults[$field]) && $defaults[$field] !== $config[$field]) {
          $form[$fieldSet]['#open'] = TRUE;
        }
      }
    }

    return $form;
  }

  /**
   * Form API callback: Processes the elements in field sets.
   *
   * Adjusts the #parents of field sets to save its children at the top level.
   */
  public static function processMenuBlockFieldSets(&$element, FormStateInterface $form_state, &$complete_form) {
    array_pop($element['#parents']);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['follow'] = $form_state->getValue('follow');
    $this->configuration['follow_parent'] = $form_state->getValue('follow_parent');
    $this->configuration['level'] = $form_state->getValue('level');
    $this->configuration['depth'] = $form_state->getValue('depth');
    $this->configuration['expand_all_items'] = (bool) $form_state->getValue('expand_all_items');
    // On save, the core config property (expand_all_items) gets updated, and
    // the contrib config property value (expand) is deleted/removed altogether.
    if (isset($this->configuration['expand'])) {
      unset($this->configuration['expand']);
    }
    $this->configuration['parent'] = $form_state->getValue('parent');
    $this->configuration['render_parent'] = $form_state->getValue('render_parent');
    $this->configuration['suggestion'] = $form_state->getValue('suggestion');
    $this->configuration['label_type'] = $form_state->getValue('label_type');
    $this->configuration['label_link'] = $form_state->getValue('label_link');
    $this->configuration['hide_on_nonactive'] = $form_state->getValue('hide_on_nonactive');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $menu_name = $this->getDerivativeId();
    $parameters = $this->menuTree->getCurrentRouteMenuTreeParameters($menu_name);
    $config = $this->configuration;

    // Check if the active trail is empty.
    if ($config['hide_on_nonactive'] && !$this->menuActiveTrail->getActiveLink($menu_name)) {
      return [];
    }

    // Adjust the menu tree parameters based on the block's configuration.
    $level = $this->configuration['level'];
    $depth = $this->configuration['depth'];
    // For blocks placed in Layout Builder or similar, check for the deprecated
    // 'expand' config property in case the menu block's configuration has not
    // yet been updated.
    $expand_all_items = $this->configuration['expand'] ?? $this->configuration['expand_all_items'];
    $parent = $this->configuration['parent'] ?? '';
    $render_parent = $this->configuration['render_parent'];
    $follow = $this->configuration['follow'];
    $follow_parent = $this->configuration['follow_parent'];
    $following = FALSE;

    // If render parent is true, we'll avoid setting a minimum depth. A
    // NULL minimum depth will cause the parent item to be included in the
    // tree by default.
    if (!$render_parent) {
      $parameters->setMinDepth($level);
    }

    // If we're following the active trail and the active trail is deeper than
    // the initial starting level, we update the level to match the active menu
    // item's level in the menu.
    if ($follow && count($parameters->activeTrail) > $level) {
      $level = count($parameters->activeTrail);
      $following = TRUE;
    }

    // When the depth is configured to zero, there is no depth limit. When depth
    // is non-zero, it indicates the number of levels that must be displayed.
    // Hence this is a relative depth that we must convert to an actual
    // (absolute) depth, that may never exceed the maximum depth.
    if ($depth > 0) {
      $parameters->setMaxDepth(min($level + $depth - 1, $this->menuTree->maxDepth()));
    }

    // If we're currently following an active menu item, or for menu blocks with
    // start level greater than 1, only show menu items from the current active
    // trail. Adjust the root according to the current position in the menu in
    // order to determine if we can show the subtree. If we're not following an
    // active trail and using a fixed parent item, we'll skip this step.
    $fixed_parent_menu_link_id = str_replace($menu_name . ':', '', $parent);
    if ($following || ($level > 1 && !$fixed_parent_menu_link_id)) {
      if (count($parameters->activeTrail) >= $level) {
        // Active trail array is child-first. Reverse it, and pull the new menu
        // root based on the parent of the configured start level.
        $menu_trail_ids = array_reverse(array_values($parameters->activeTrail));
        $offset = ($following && $follow_parent == 'active') ? 2 : 1;
        $menu_root = $menu_trail_ids[$level - $offset];
        $parameters->setRoot($menu_root);
        if ($following || !$render_parent) {
          $parameters->setMinDepth(1);
        }
        if ($depth > 0) {
          $parameters->setMaxDepth(min($depth, $this->menuTree->maxDepth()));
        }
      }
      else {
        return [];
      }
    }

    // If expandedParents is empty, the whole menu tree is built.
    if ($expand_all_items) {
      $parameters->expandedParents = [];
    }

    // When a fixed parent item is set, root the menu tree at the given ID.
    if ($fixed_parent_menu_link_id) {
      // Clone the parameters so we can fall back to using them if we're
      // following the active menu item and the current page is part of the
      // active menu trail.
      $fixed_parameters = clone $parameters;
      $fixed_parameters->setRoot($fixed_parent_menu_link_id);
      $tree = $this->menuTree->load($menu_name, $fixed_parameters);

      // Check if the tree contains links.
      if (empty($tree)) {
        // If the starting level is 1, we always want the child links to appear,
        // but the requested tree may be empty if the tree does not contain the
        // active trail. We're accessing the configuration directly since the
        // $level variable may have changed by this point.
        if ($this->configuration['level'] === 1 || $this->configuration['level'] === '1') {
          // Change the request to expand all children and limit the depth to
          // the immediate children of the root.
          $fixed_parameters->expandedParents = [];
          if (!$render_parent) {
            $fixed_parameters->setMinDepth(1);
          }
          $fixed_parameters->setMaxDepth(1);
          // Re-load the tree.
          $tree = $this->menuTree->load($menu_name, $fixed_parameters);
        }
      }
      elseif ($following) {
        // If we're following the active menu item, and the tree isn't empty
        // (which indicates we're currently in the active trail), we unset
        // the tree we made and just let the active menu parameters from before
        // do their thing.
        unset($tree);
      }

      // If render parent is true, we avoid setting min depth. When a fixed
      // parent has also been chosen, this could result in a menu tree even
      // though the current item is not in the active trail.
      if ($render_parent) {
        // If the fixed parent item isn't in the active trail and starting level
        // isn't 1 (which means ALWAYS show the menu), don't render anything.
        if (!in_array($fixed_parent_menu_link_id, $parameters->activeTrail) && (int) $this->configuration['level'] <> 1) {
          return [];
        }
      }
    }

    // Load the tree if we haven't already.
    if (!isset($tree)) {
      $tree = $this->menuTree->load($menu_name, $parameters);
    }
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuTree->transform($tree, $manipulators);
    $build = $this->menuTree->build($tree);

    $label = $this->getBlockLabel() ?: $this->label();
    // Set the block's #title (label) to the dynamic value.
    $build['#title'] = [
      '#markup' => $label,
    ];
    if (!empty($build['#theme'])) {
      // Add the configuration for use in menu_block_theme_suggestions_menu().
      $build['#menu_block_configuration'] = $this->configuration;
      // Set the generated label into the configuration array so it is
      // propagated to the theme preprocessor and template(s) as needed.
      $build['#menu_block_configuration']['label'] = $label;
      // Remove the menu name-based suggestion so we can control its precedence
      // better in menu_block_theme_suggestions_menu().
      $build['#theme'] = 'menu';
    }

    $build['#contextual_links']['menu'] = [
      'route_parameters' => ['menu' => $menu_name],
    ];

    $build['#cache']['contexts'][] = 'route.menu_active_trails:' . $menu_name;

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'follow' => 0,
      'follow_parent' => 'child',
      'level' => 1,
      'depth' => 0,
      'expand_all_items' => FALSE,
      'parent' => $this->getDerivativeId() . ':',
      'render_parent' => FALSE,
      'suggestion' => strtr($this->getDerivativeId(), '-', '_'),
      'label_type' => self::LABEL_BLOCK,
      'label_link' => FALSE,
      'hide_on_nonactive' => FALSE,
    ];
  }

  /**
   * Checks for an existing theme hook suggestion.
   *
   * @return bool
   *   Returns FALSE because there is no need of validation by unique value.
   */
  public function suggestionExists() {
    return FALSE;
  }

  /**
   * Gets the configured block label.
   *
   * @return string
   *   The configured label.
   */
  public function getBlockLabel() {
    switch ($this->configuration['label_type']) {
      case self::LABEL_MENU:
        return $this->getMenuTitle();

      case self::LABEL_ACTIVE_ITEM:
        return $this->getActiveItemTitle();

      case self::LABEL_PARENT:
        return $this->getActiveTrailParentTitle();

      case self::LABEL_ROOT:
        return $this->getActiveTrailRootTitle();

      case self::LABEL_FIXED:
        return $this->getFixedMenuItemTitle();

      default:
        return $this->label();
    }
  }

  /**
   * Gets the label of the configured menu.
   *
   * @return string|null
   *   Menu label or NULL if no menu exists.
   */
  protected function getMenuTitle() {
    try {
      $menu = $this->entityTypeManager->getStorage('menu')
        ->load($this->getDerivativeId());
    }
    catch (\Exception) {
      return NULL;
    }

    return $menu ? $menu->label() : NULL;
  }

  /**
   * Gets the title of a fixed parent item.
   *
   * @return string|null
   *   Title of the configured (fixed) parent item, or NULL if there is none.
   */
  protected function getFixedMenuItemTitle() {
    $parent = $this->configuration['parent'];

    if ($parent) {
      $fixed_menu_link_id = str_replace($this->getDerivativeId() . ':', '', $parent);
      return $this->getLinkTitleFromLink($fixed_menu_link_id);
    }
    return NULL;
  }

  /**
   * Gets the active menu item's title.
   *
   * @return string|null
   *   Currently active menu item title or NULL if there's nothing active.
   */
  protected function getActiveItemTitle() {
    /** @var array $active_trail_ids */
    $active_trail_ids = $this->getDerivativeActiveTrailIds();
    if ($active_trail_ids) {
      return $this->getLinkTitleFromLink(reset($active_trail_ids));
    }
    return NULL;
  }

  /**
   * Gets the title of the parent of the active menu item.
   *
   * @return string|null
   *   The title of the parent of the active menu item, the title of the active
   *   item if it has no parent, or NULL if there's no active menu item.
   */
  protected function getActiveTrailParentTitle() {
    /** @var array $active_trail_ids */
    $active_trail_ids = $this->getDerivativeActiveTrailIds();
    if ($active_trail_ids) {
      if (count($active_trail_ids) === 1) {
        return $this->getActiveItemTitle();
      }
      return $this->getLinkTitleFromLink(next($active_trail_ids));
    }
    return NULL;
  }

  /**
   * Gets the current menu item's root menu item title.
   *
   * @return string|null
   *   The root menu item title or NULL if there's no active item.
   */
  protected function getActiveTrailRootTitle() {
    /** @var array $active_trail_ids */
    $active_trail_ids = $this->getDerivativeActiveTrailIds();

    if ($active_trail_ids) {
      return $this->getLinkTitleFromLink(end($active_trail_ids));
    }
    return NULL;
  }

  /**
   * Gets an array of the active trail menu link items.
   *
   * @return array
   *   The active trail menu item IDs.
   */
  protected function getDerivativeActiveTrailIds() {
    $menu_id = $this->getDerivativeId();
    return array_filter($this->menuActiveTrail->getActiveTrailIds($menu_id));
  }

  /**
   * Gets the title of a given menu item ID.
   *
   * @param string $link_id
   *   The menu item ID.
   *
   * @return string|null
   *   The menu item title or NULL if the given menu item can't be found.
   */
  protected function getLinkTitleFromLink(string $link_id) {
    // Get the actual parameters so we have the active trail.
    $menu_name = $this->getDerivativeId();
    $parameters = $this->menuTree->getCurrentRouteMenuTreeParameters($menu_name);
    $menu = $this->menuTree->load($menu_name, $parameters);
    $link = $this->findLinkInTree($menu, $link_id);
    if ($link) {
      if ($this->configuration['label_link']) {
        /** @var \Drupal\Core\Url $url */
        $url = $link->link->getUrlObject();

        // Trigger drupal.active-link to set the is_active class.
        $url->setOption('set_active_class', TRUE);

        // Set the active trail class.
        if (in_array($link_id, $parameters->activeTrail)) {
          $attributes = $url->getOption('attributes');
          $attributes['class'][] = 'menu-item--active-trail';
          $url->setOption('attributes', $attributes);
        }

        $block_link = Link::fromTextAndUrl($link->link->getTitle(), $url)->toString();
        return Markup::create($block_link);
      }
      return $link->link->getTitle();
    }
    return NULL;
  }

  /**
   * Gets the menu link item from the menu tree.
   *
   * @param array $menu_tree
   *   Associative array containing the menu link tree data.
   * @param string $link_id
   *   Menu link id to find.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement|null
   *   The link element from the given menu tree or NULL if it can't be found.
   */
  protected function findLinkInTree(array $menu_tree, $link_id) {
    if (isset($menu_tree[$link_id])) {
      return $menu_tree[$link_id];
    }
    /** @var \Drupal\Core\Menu\MenuLinkTreeElement $link */
    foreach ($menu_tree as $link) {
      $link = $this->findLinkInTree($link->subtree, $link_id);
      if ($link) {
        return $link;
      }
    }
    return NULL;
  }

}
