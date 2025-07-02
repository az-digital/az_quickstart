<?php

namespace Drupal\devel_generate\Plugin\DevelGenerate;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\devel_generate\DevelGenerateBase;
use Drupal\menu_link_content\MenuLinkContentStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a MenuDevelGenerate plugin.
 *
 * @DevelGenerate(
 *   id = "menu",
 *   label = @Translation("menus"),
 *   description = @Translation("Generate a given number of menus and menu
 *   links. Optionally delete current menus."), url = "menu", permission =
 *   "administer devel_generate", settings = {
 *     "num_menus" = 2,
 *     "num_links" = 50,
 *     "title_length" = 12,
 *     "max_width" = 6,
 *     "kill" = FALSE,
 *   }
 * )
 */
class MenuDevelGenerate extends DevelGenerateBase implements ContainerFactoryPluginInterface {

  /**
   * The menu tree service.
   */
  protected MenuLinkTreeInterface $menuLinkTree;

  /**
   * The menu storage.
   */
  protected EntityStorageInterface $menuStorage;

  /**
   * The menu link storage.
   */
  protected MenuLinkContentStorageInterface $menuLinkContentStorage;

  /**
   * Database connection.
   */
  protected Connection $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $entity_type_manager = $container->get('entity_type.manager');
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->menuLinkTree = $container->get('menu.link_tree');
    $instance->menuStorage = $entity_type_manager->getStorage('menu');
    $instance->menuLinkContentStorage = $entity_type_manager->getStorage('menu_link_content');
    $instance->database = $container->get('database');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $menus = array_map(static fn($menu) => $menu->label(), $this->menuStorage->loadMultiple());
    asort($menus);
    $menus = ['__new-menu__' => $this->t('Create new menu(s)')] + $menus;
    $form['existing_menus'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Generate links for these menus'),
      '#options' => $menus,
      '#default_value' => ['__new-menu__'],
      '#required' => TRUE,
    ];
    $form['num_menus'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of new menus to create'),
      '#default_value' => $this->getSetting('num_menus'),
      '#min' => 0,
      '#states' => [
        'visible' => [
          ':input[name="existing_menus[__new-menu__]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['num_links'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of links to generate'),
      '#default_value' => $this->getSetting('num_links'),
      '#required' => TRUE,
      '#min' => 0,
    ];
    $form['title_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum length for menu titles and menu links'),
      '#description' => $this->t('Text will be generated at random lengths up to this value. Enter a number between 2 and 128.'),
      '#default_value' => $this->getSetting('title_length'),
      '#required' => TRUE,
      '#min' => 2,
      '#max' => 128,
    ];
    $form['link_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Types of links to generate'),
      '#options' => [
        'node' => $this->t('Nodes'),
        'front' => $this->t('Front page'),
        'external' => $this->t('External'),
      ],
      '#default_value' => ['node', 'front', 'external'],
      '#required' => TRUE,
    ];
    $form['max_depth'] = [
      '#type' => 'select',
      '#title' => $this->t('Maximum link depth'),
      '#options' => range(0, $this->menuLinkTree->maxDepth()),
      '#default_value' => floor($this->menuLinkTree->maxDepth() / 2),
      '#required' => TRUE,
    ];
    unset($form['max_depth']['#options'][0]);
    $form['max_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum menu width'),
      '#default_value' => $this->getSetting('max_width'),
      '#description' => $this->t("Limit the width of the generated menu's first level of links to a certain number of items."),
      '#required' => TRUE,
      '#min' => 0,
    ];
    $form['kill'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete existing custom generated menus and menu links before generating new ones.'),
      '#default_value' => $this->getSetting('kill'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function generateElements(array $values): void {
    // If the create new menus checkbox is off, set the number of menus to 0.
    if (!isset($values['existing_menus']['__new-menu__']) || !$values['existing_menus']['__new-menu__']) {
      $values['num_menus'] = 0;
    }
    else {
      // Unset the aux menu to avoid attach menu new items.
      unset($values['existing_menus']['__new-menu__']);
    }

    // Delete custom menus.
    if ($values['kill']) {
      [$menus_deleted, $links_deleted] = $this->deleteMenus();
      $this->setMessage($this->t('Deleted @menus_deleted menu(s) and @links_deleted other link(s).',
        [
          '@menus_deleted' => $menus_deleted,
          '@links_deleted' => $links_deleted,
        ]));
    }

    // Generate new menus.
    $new_menus = $this->generateMenus($values['num_menus'], $values['title_length']);
    if ($new_menus !== []) {
      $this->setMessage($this->formatPlural(count($new_menus), 'Created the following 1 new menu: @menus', 'Created the following @count new menus: @menus',
        ['@menus' => implode(', ', $new_menus)]));
    }

    // Generate new menu links.
    $menus = $new_menus;
    if (isset($values['existing_menus'])) {
      $menus += $values['existing_menus'];
    }

    $new_links = $this->generateLinks($values['num_links'], $menus, $values['title_length'], $values['link_types'], $values['max_depth'], $values['max_width']);
    $this->setMessage($this->formatPlural(count($new_links), 'Created 1 new menu link.', 'Created @count new menu links.'));
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrushParams(array $args, array $options = []): array {
    $link_types = ['node', 'front', 'external'];
    $values = [
      'num_menus' => array_shift($args),
      'num_links' => array_shift($args),
      'kill' => $options['kill'],
      'pipe' => $options['pipe'],
      'link_types' => array_combine($link_types, $link_types),
    ];

    $max_depth = array_shift($args);
    $max_width = array_shift($args);
    $values['max_depth'] = $max_depth ?: 3;
    $values['max_width'] = $max_width ?: 8;
    $values['title_length'] = $this->getSetting('title_length');
    $values['existing_menus']['__new-menu__'] = TRUE;

    if ($this->isNumber($values['num_menus']) == FALSE) {
      throw new \Exception(dt('Invalid number of menus'));
    }

    if ($this->isNumber($values['num_links']) == FALSE) {
      throw new \Exception(dt('Invalid number of links'));
    }

    if ($this->isNumber($values['max_depth']) == FALSE || $values['max_depth'] > 9 || $values['max_depth'] < 1) {
      throw new \Exception(dt('Invalid maximum link depth. Use a value between 1 and 9'));
    }

    if ($this->isNumber($values['max_width']) == FALSE || $values['max_width'] < 1) {
      throw new \Exception(dt('Invalid maximum menu width. Use a positive numeric value.'));
    }

    return $values;
  }

  /**
   * Deletes custom generated menus.
   */
  protected function deleteMenus(): array {
    $menu_ids = [];
    if ($this->moduleHandler->moduleExists('menu_ui')) {
      $all = $this->menuStorage->loadMultiple();
      foreach ($all as $menu) {
        if (str_starts_with($menu->id(), 'devel-')) {
          $menu_ids[] = $menu->id();
        }
      }

      if ($menu_ids !== []) {
        $menus = $this->menuStorage->loadMultiple($menu_ids);
        $this->menuStorage->delete($menus);
      }
    }

    // Delete menu links in other menus, but generated by devel.
    $link_ids = $this->menuLinkContentStorage->getQuery()
      ->condition('menu_name', 'devel', '<>')
      ->condition('link__options', '%' . $this->database->escapeLike('s:5:"devel";b:1') . '%', 'LIKE')
      ->accessCheck(FALSE)
      ->execute();

    if ($link_ids) {
      $links = $this->menuLinkContentStorage->loadMultiple($link_ids);
      $this->menuLinkContentStorage->delete($links);
    }

    return [count($menu_ids), count($link_ids)];
  }

  /**
   * Generates new menus.
   *
   * @param int $num_menus
   *   Number of menus to create.
   * @param int $title_length
   *   (optional) Maximum length of menu name.
   *
   * @return array
   *   Array containing the generated menus.
   */
  protected function generateMenus(int $num_menus, int $title_length = 12): array {
    $menus = [];

    for ($i = 1; $i <= $num_menus; ++$i) {
      $name = $this->randomSentenceOfLength(mt_rand(2, $title_length));
      // Create a random string of random length for the menu id. The maximum
      // machine-name length is 32, so allowing for prefix 'devel-' we can have
      // up to 26 here. For safety avoid accidentally reusing the same id.
      do {
        $id = 'devel-' . $this->getRandom()->word(mt_rand(2, 26));
      } while (array_key_exists($id, $menus));

      $menu = $this->menuStorage->create([
        'label' => $name,
        'id' => $id,
        'description' => $this->t('Description of @name', ['@name' => $name]),
      ]);

      $menu->save();
      $menus[$menu->id()] = $menu->label();
    }

    return $menus;
  }

  /**
   * Generates menu links in a tree structure.
   *
   * @return array<int|string, string>
   *   Array containing the titles of the generated menu links.
   */
  protected function generateLinks(int $num_links, array $menus, int $title_length, array $link_types, int $max_depth, int $max_width): array {
    $links = [];
    $menus = array_keys(array_filter($menus));
    $link_types = array_keys(array_filter($link_types));

    $nids = [];
    for ($i = 1; $i <= $num_links; ++$i) {
      // Pick a random menu.
      $menu_name = $menus[array_rand($menus)];
      // Build up our link.
      $link_title = $this->getRandom()->word(mt_rand(2, max(2, $title_length)));

      /** @var \Drupal\menu_link_content\MenuLinkContentInterface $menuLinkContent */
      $menuLinkContent = $this->menuLinkContentStorage->create([
        'menu_name' => $menu_name,
        'weight' => mt_rand(-50, 50),
        'title' => $link_title,
        'bundle' => 'menu_link_content',
        'description' => $this->t('Description of @title.', ['@title' => $link_title]),
      ]);
      $link = $menuLinkContent->get('link');
      $options['devel'] = TRUE;
      $link->setValue(['options' => $options]);

      // For the first $max_width items, make first level links, otherwise, get
      // a random parent menu depth.
      $max_link_depth = $i <= $max_width ? 0 : mt_rand(1, max(1, $max_depth - 1));

      // Get a random parent link from the proper depth.
      for ($depth = $max_link_depth; $depth >= 0; --$depth) {
        $parameters = new MenuTreeParameters();
        $parameters->setMinDepth($depth);
        $parameters->setMaxDepth($depth);
        $tree = $this->menuLinkTree->load($menu_name, $parameters);
        if ($tree === []) {
          continue;
        }

        $menuLinkContent->set('parent', array_rand($tree));
        break;
      }

      $link_type = array_rand($link_types);
      switch ($link_types[$link_type]) {
        case 'node':
          // Grab a random node ID.
          $select = $this->database->select('node_field_data', 'n')
            ->fields('n', ['nid', 'title'])
            ->condition('n.status', 1)
            ->range(0, 1)
            ->orderRandom();
          // Don't put a node into the menu twice.
          if (isset($nids[$menu_name])) {
            $select->condition('n.nid', $nids[$menu_name], 'NOT IN');
          }

          $node = $select->execute()->fetchAssoc();
          if (isset($node['nid'])) {
            $nids[$menu_name][] = $node['nid'];
            $link->setValue(['uri' => 'entity:node/' . $node['nid']]);
            $menuLinkContent->set('title', $node['title']);
            break;
          }

        case 'external':
          $link->setValue(['uri' => 'https://www.example.com/']);
          break;

        case 'front':
          $link->setValue(['uri' => 'internal:/<front>']);
          break;

        default:
          break;
      }

      $menuLinkContent->save();

      $links[$menuLinkContent->id()] = $menuLinkContent->getTitle();
    }

    return $links;
  }

}
