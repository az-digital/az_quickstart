<?php

namespace Drupal\az_core\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
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
 * navigation display.
 */
#[Block(
  id: "az_navbar_fullscreen_menu_block",
  admin_label: new TranslatableMarkup("AZ Navbar Fullscreen Menu (experimental)"),
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
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'primary_menu' => 'main',
      'cta_menu' => '',
      'footer_top_menu' => '',
      'footer_top_desktop_heading' => 'Resources For',
      'footer_top_mobile_heading' => 'Resources by Audience',
      'footer_bottom_menu' => '',
      'footer_bottom_desktop_heading' => 'Helpful Links',
      'footer_bottom_mobile_heading' => 'Helpful Links',
      'search_form_block_desktop_navbar' => '',
      'search_form_block_offcanvas' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->configuration;

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

    $form['menu_selection']['footer_top_menu'] = [
      '#type' => 'select',
      '#title' => $this->t('Footer Top Menu'),
      '#default_value' => $config['footer_top_menu'],
      '#options' => ['' => $this->t('- None -')] + $menus,
      '#description' => $this->t('Select the menu to use for the top footer.'),
    ];

    $form['menu_selection']['footer_top_desktop_heading'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Footer Top Desktop Heading'),
      '#default_value' => $config['footer_top_desktop_heading'],
      '#description' => $this->t('Heading for the top footer on desktop devices.'),
    ];

    $form['menu_selection']['footer_top_mobile_heading'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Footer Top Mobile Heading'),
      '#default_value' => $config['footer_top_mobile_heading'],
      '#description' => $this->t('Heading for the top footer on mobile devices. Since the mobile footer will lack the extra context of the footer links, the mobile heading may need to be more descriptive than the desktop heading.'),
    ];

    $form['menu_selection']['footer_bottom_menu'] = [
      '#type' => 'select',
      '#title' => $this->t('Footer Bottom Menu'),
      '#default_value' => $config['footer_bottom_menu'],
      '#options' => ['' => $this->t('- None -')] + $menus,
      '#description' => $this->t('Select the menu to use for the bottom footer.'),
    ];

    $form['menu_selection']['footer_bottom_desktop_heading'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Footer Bottom Desktop Heading'),
      '#default_value' => $config['footer_bottom_desktop_heading'],
      '#description' => $this->t('Heading for the bottom footer on desktop devices.'),
    ];

    $form['menu_selection']['footer_bottom_mobile_heading'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Footer Bottom Mobile Heading'),
      '#default_value' => $config['footer_bottom_mobile_heading'],
      '#description' => $this->t('Heading for the bottom footer on mobile devices. Since the mobile footer will lack the extra context of the footer links, the mobile heading may need to be more descriptive than the desktop heading.'),
    ];

    // Load search form blocks.
    $search_blocks = [];
    $blocks = $this->entityTypeManager->getStorage('block')->loadMultiple();
    foreach ($blocks as $block) {
      if ($block->getPluginId() === 'search_form_block') {
        $search_blocks[$block->id()] = $block->label() . ' (' . $block->id() . ')';
      }
    }

    $form['search_form_block_desktop_navbar'] = [
      '#type' => 'select',
      '#title' => $this->t('Search Form Block - Desktop Navbar'),
      '#default_value' => $config['search_form_block_desktop_navbar'] ?? '',
      '#options' => ['' => $this->t('- None -')] + $search_blocks,
      '#description' => $this->t('Select the search form block to display in the navbar on desktop devices.'),
    ];

    $form['search_form_block_offcanvas'] = [
      '#type' => 'select',
      '#title' => $this->t('Search Form Block - Offcanvas'),
      '#default_value' => $config['search_form_block_offcanvas'] ?? '',
      '#options' => ['' => $this->t('- None -')] + $search_blocks,
      '#description' => $this->t('Select the search form block to display in the offcanvas menu.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['primary_menu'] = $form_state->getValue('primary_menu');
    $menu_selection = $form_state->getValue('menu_selection');
    $this->configuration['cta_menu'] = $menu_selection['cta_menu'] ?? '';
    $this->configuration['footer_top_menu'] = $menu_selection['footer_top_menu'] ?? '';
    $this->configuration['footer_top_desktop_heading'] = $menu_selection['footer_top_desktop_heading'] ?? '';
    $this->configuration['footer_top_mobile_heading'] = $menu_selection['footer_top_mobile_heading'] ?? '';
    $this->configuration['footer_bottom_menu'] = $menu_selection['footer_bottom_menu'] ?? '';
    $this->configuration['footer_bottom_desktop_heading'] = $menu_selection['footer_bottom_desktop_heading'] ?? '';
    $this->configuration['footer_bottom_mobile_heading'] = $menu_selection['footer_bottom_mobile_heading'] ?? '';
    $this->configuration['search_form_block_desktop_navbar'] = $form_state->getValue('search_form_block_desktop_navbar') ?? '';
    $this->configuration['search_form_block_offcanvas'] = $form_state->getValue('search_form_block_offcanvas') ?? '';
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
      'footer_top_menu' => 'footer_top',
      'footer_bottom_menu' => 'footer_bottom',
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

    // Add footer headings.
    $build['footer_top_desktop_heading'] = $this->configuration['footer_top_desktop_heading'] ?? '';
    $build['footer_top_mobile_heading'] = $this->configuration['footer_top_mobile_heading'] ?? '';
    $build['footer_bottom_desktop_heading'] = $this->configuration['footer_bottom_desktop_heading'] ?? '';
    $build['footer_bottom_mobile_heading'] = $this->configuration['footer_bottom_mobile_heading'] ?? '';

    // Add search form block for the desktop navbar if configured.
    if (!empty($this->configuration['search_form_block_desktop_navbar'])) {
      $search_form_block_desktop_navbar_id = $this->configuration['search_form_block_desktop_navbar'];
      $search_form_block_desktop_navbar = $this->entityTypeManager->getStorage('block')->load($search_form_block_desktop_navbar_id);

      if ($search_form_block_desktop_navbar) {
        $build['search_form_block_desktop_navbar'] = $this->entityTypeManager->getViewBuilder('block')->view($search_form_block_desktop_navbar);
        $build['#cache']['tags'][] = "config:block.block.{$search_form_block_desktop_navbar_id}";
      }
    }

    // Add search form block for the offcanvas modal if configured.
    if (!empty($this->configuration['search_form_block_offcanvas'])) {
      $search_form_block_offcanvas_id = $this->configuration['search_form_block_offcanvas'];
      $search_form_block_offcanvas = $this->entityTypeManager->getStorage('block')->load($search_form_block_offcanvas_id);

      if ($search_form_block_offcanvas) {
        /* Render with the generic search form block template.
         * @todo Consider other solutions to avoid applying classes
         * from the az-barrio-offcanvas-searchform template.
         */
        $plugin = $search_form_block_offcanvas->getPlugin();
        $build['search_form_block_offcanvas'] = $plugin->build();
        $build['#cache']['tags'][] = "config:block.block.{$search_form_block_offcanvas_id}";
      }
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

    // Skip disabled links in the menu.
    $parameters->onlyEnabledLinks();

    if ($section_key === 'primary') {
      // Primary menu: Set the active trail and limit depth to 3.
      $parameters->setActiveTrail($this->menuActiveTrail->getActiveTrailIds($menu_name));
      $parameters->setMaxDepth(3);
    }
    else {
      // Additional menus: only keep the first level of menu items.
      $parameters->setMaxDepth(1);
    }

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
    if (!empty($this->configuration['footer_top_menu'])) {
      $cache_tags[] = 'config:system.menu.' . $this->configuration['footer_top_menu'];
    }
    if (!empty($this->configuration['footer_bottom_menu'])) {
      $cache_tags[] = 'config:system.menu.' . $this->configuration['footer_bottom_menu'];
    }
    return $cache_tags;
  }

}
