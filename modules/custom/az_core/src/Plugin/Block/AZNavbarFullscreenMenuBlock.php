<?php

namespace Drupal\az_core\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a AZ Navbar Fullscreen Menu Block.
 *
 * This block allows selection of three additional menus for the fullscreen
 * navigation display, with configuration options for menu depth and visibility
 * similar to the Drupal core System Menu Block.
 */
#[Block(
  id: "az_navbar_fullscreen_menu_block",
  admin_label: new TranslatableMarkup("AZ Navbar Fullscreen Menu"),
  category: new TranslatableMarkup("Menus")
)]
class AZNavbarFullscreenMenuBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * The active menu trail service.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a AZNavbarFullscreenMenuBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_link_tree
   *   The menu link tree service.
   * @param \Drupal\Core\Menu\MenuActiveTrailInterface $menu_active_trail
   *   The active menu trail service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MenuLinkTreeInterface $menu_link_tree,
    MenuActiveTrailInterface $menu_active_trail,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->menuLinkTree = $menu_link_tree;
    $this->menuActiveTrail = $menu_active_trail;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu.link_tree'),
      $container->get('menu.active_trail'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'primary_menu' => 'main',
      'level' => 1,
      'depth' => NULL,
      'expand_all_items' => FALSE,
      'cta_menu' => '',
      'resources_menu' => '',
      'helpful_links_menu' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->configuration;
    $defaults = $this->defaultConfiguration();

    // Get list of available menus.
    $menus = $this->entityTypeManager->getStorage('menu')->loadMultiple();
    foreach ($menus as $menu) {
      $menus[$menu->id()] = $menu->label();
    }

    $form['primary_menu'] = [
      '#type' => 'select',
      '#title' => $this->t('Primary menu'),
      '#default_value' => $config['primary_menu'] ?? 'main',
      '#options' => ['' => $this->t('- None -')] + $menus,
      '#description' => $this->t('Select the primary menu for the fullscreen navigation.'),
    ];

    // Add the menu levels configuration from SystemMenuBlock.
    $form['menu_levels'] = [
      '#type' => 'details',
      '#title' => $this->t('Menu levels'),
      // Open if not set to defaults.
      '#open' => $defaults['level'] !== $config['level'] || $defaults['depth'] !== $config['depth'],
      '#process' => [[static::class, 'processMenuLevelParents']],
    ];

    $options = range(0, $this->menuLinkTree->maxDepth());
    unset($options[0]);

    $form['menu_levels']['level'] = [
      '#type' => 'select',
      '#title' => $this->t('Initial visibility level'),
      '#default_value' => $config['level'],
      '#options' => $options,
      '#description' => $this->t('The menu is only visible if the menu link for the current page is at this level or below it. Use level 1 to always display this menu.'),
      '#required' => TRUE,
    ];

    $options[0] = $this->t('Unlimited');
    $form['menu_levels']['depth'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of levels to display'),
      '#default_value' => $config['depth'] ?? 0,
      '#options' => $options,
      '#description' => $this->t('This maximum number includes the initial level.'),
      '#required' => TRUE,
    ];

    $form['menu_levels']['expand_all_items'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Expand all menu links'),
      '#default_value' => !empty($config['expand_all_items']),
      '#description' => $this->t('Override the option found on each menu link used for expanding children and instead display the whole menu tree as expanded.'),
    ];

    $form['menu_selection'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Additional menus'),
      '#tree' => TRUE,
    ];

    $form['menu_selection']['cta_menu'] = [
      '#type' => 'select',
      '#title' => $this->t('Call to Action Menu'),
      '#default_value' => $config['cta_menu'],
      '#options' => ['' => $this->t('- None -')] + $menus,
      '#description' => $this->t('Select the menu to use for the Call to Action section.'),
    ];

    $form['menu_selection']['resources_menu'] = [
      '#type' => 'select',
      '#title' => $this->t('Resources For Menu'),
      '#default_value' => $config['resources_menu'],
      '#options' => ['' => $this->t('- None -')] + $menus,
      '#description' => $this->t('Select the menu to use for the Resources For section.'),
    ];

    $form['menu_selection']['helpful_links_menu'] = [
      '#type' => 'select',
      '#title' => $this->t('Helpful Links Menu'),
      '#default_value' => $config['helpful_links_menu'],
      '#options' => ['' => $this->t('- None -')] + $menus,
      '#description' => $this->t('Select the menu to use for the Helpful Links section.'),
    ];

    return $form;
  }

  /**
   * Form API callback: Processes the menu_levels field element.
   *
   * Adjusts the #parents of menu_levels to save its children at the top level.
   */
  public static function processMenuLevelParents(&$element, FormStateInterface $form_state, &$complete_form) {
    array_pop($element['#parents']);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['primary_menu'] = $form_state->getValue('primary_menu');
    $this->configuration['level'] = $form_state->getValue('level');
    $this->configuration['depth'] = $form_state->getValue('depth') ?: NULL;
    $this->configuration['expand_all_items'] = $form_state->getValue('expand_all_items');
    $menu_selection = $form_state->getValue('menu_selection');
    $this->configuration['cta_menu'] = $menu_selection['cta_menu'] ?? '';
    $this->configuration['resources_menu'] = $menu_selection['resources_menu'] ?? '';
    $this->configuration['helpful_links_menu'] = $menu_selection['helpful_links_menu'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      '#cache' => [
        'contexts' => ['route'],
        'tags' => [],
      ],
    ];

    // Load and build each of the three additional menus.
    $menus_config = [
      'primary_menu' => 'primary',
      'cta_menu' => 'cta',
      'resources_menu' => 'resources',
      'helpful_links_menu' => 'helpful_links',
    ];

    foreach ($menus_config as $config_key => $section_key) {
      $menu_name = $this->configuration[$config_key] ?? NULL;

      if (!$menu_name) {
        continue;
      }

      $build[$section_key] = $this->buildMenuSection($menu_name, $section_key);

      // Add cache tags for the menu.
      $build['#cache']['tags'][] = "config:system.menu.{$menu_name}";
    }

    return $build;
  }

  /**
   * Build a menu section with items based on configuration.
   *
   * @param string $menu_name
   *   The machine name of the menu to load.
   * @param string $section_key
   *   The key for this menu section in the build array.
   *
   * @return array
   *   A render array containing the menu section.
   */
  protected function buildMenuSection($menu_name, $section_key) {

    $parameters = new MenuTreeParameters();

    // Only keep first level of menu items from additional menus.
    if ($section_key !== 'primary') {
      $parameters->setMaxDepth(1);
    }

    // Skip disabled links in the menu.
    $parameters->onlyEnabledLinks();

    // Load the menu tree.
    /** @var \Drupal\Core\Menu\MenuLinkTreeElement[] $tree */
    $tree = $this->menuLinkTree->load($menu_name, $parameters);

    // Apply manipulators to filter and sort the tree.
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkNodeAccess'],
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuLinkTree->transform($tree, $manipulators);

    // Use the standard menu build function to render the tree.
    return $this->menuLinkTree->build($tree);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $cache_tags = parent::getCacheTags();
    // Add cache tags for each configured menu.
    if (!empty($this->configuration['primary_menu'])) {
      $cache_tags[] = 'config:system.menu.' . $this->configuration['primary_menu'];
    }
    if (!empty($this->configuration['cta_menu'])) {
      $cache_tags[] = 'config:system.menu.' . $this->configuration['cta_menu'];
    }
    if (!empty($this->configuration['resources_menu'])) {
      $cache_tags[] = 'config:system.menu.' . $this->configuration['resources_menu'];
    }
    if (!empty($this->configuration['helpful_links_menu'])) {
      $cache_tags[] = 'config:system.menu.' . $this->configuration['helpful_links_menu'];
    }
    return $cache_tags;
  }

}
