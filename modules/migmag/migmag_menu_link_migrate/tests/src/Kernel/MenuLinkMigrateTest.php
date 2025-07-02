<?php

namespace Drupal\Tests\migmag_menu_link_migrate\Kernel;

use Drupal\Tests\migmag\Traits\MigMagKernelTestDxTrait;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\menu_link_content\MenuLinkContentInterface;

/**
 * Tests the enhanced menu link migration.
 *
 * @group migmag_menu_link_migrate
 */
class MenuLinkMigrateTest extends MigrateDrupalTestBase {

  use MigMagKernelTestDxTrait;
  use UserCreationTrait;

  /**
   * Ignored menu link properties if comparing.
   *
   * @const string[]
   */
  const IGNORED_PROPERTIES = [
    'changed',
    'revision_id',
    'revision_created',
    'revision_user',
    'revision_log_message',
    'uuid',
  ];

  /**
   * Base menu link property values for simplify comparison.
   *
   * @const array[]
   */
  const MENU_LINK_EXPECTATION_BASE = [
    'langcode' => [['value' => 'und']],
    'bundle' => [['value' => 'menu_link_content']],
    'description' => [],
    'menu_name' => [['value' => 'admin']],
    'external' => [['value' => 0]],
    'rediscover' => [['value' => 0]],
    'weight' => [['value' => 0]],
    'expanded' => [['value' => 0]],
    'parent' => [],
    'default_langcode' => [['value' => 1]],
    'revision_default' => [['value' => 1]],
    'revision_translation_affected' => [['value' => 1]],
  ];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'contact',
    'link',
    'menu_link_content',
    'migmag_menu_link_migrate',
    'migmag_process',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('menu_link_content');
    $this->installSchema('node', ['node_access']);
    $this->setUpCurrentUser(['uid' => 0]);
  }

  /**
   * Checks the altered migrations.
   */
  public function testMigrationAlterations() {
    $this->prepareCoreTest();

    // Check the source config (since we're adding constants there)
    // and the order of the migration processes (since we're adding new process
    // pipelines and we also modify destination property order).
    $menu_link_migration = $this->getMigration('d7_menu_links');
    $this->assertEquals(
      [
        'plugin' => 'menu_link',
        'constants' => [
          'bundle' => 'menu_link_content',
          'missing_path_prefix' => " (unavailable: '",
          'missing_path_suffix' => "')",
          'plugin_prefix' => 'menu_link_content',
        ],
      ],
      $menu_link_migration->getSourceConfiguration()
    );
    $this->assertSame(
      [
        'skip_translation',
        'id',
        'langcode',
        'bundle',
        'description',
        'menu_name',
        'link/uri',
        'path_is_available',
        'stub',
        'title_suffix',
        'original_title',
        'title',
        'link/options',
        'route',
        'route_name',
        'route_parameters',
        'url',
        'options',
        'external',
        'weight',
        'expanded',
        'original_enabled',
        'enabled',
        'original_parent',
        'parent_uuid',
        'lookup_stub_parent',
        'parent',
        'changed',
      ],
      array_keys($menu_link_migration->getProcess())
    );

    $node_translation_menu_links_migration = $this->getMigration('node_translation_menu_links');
    $this->assertEquals(
      [
        'plugin' => 'menu_link',
        'constants' => [
          'entity_prefix' => 'entity:',
          'node_prefix' => 'node/',
          'missing_path_prefix' => " (unavailable: '",
          'missing_path_suffix' => "')",
          'plugin_prefix' => 'menu_link_content',
        ],
      ],
      $node_translation_menu_links_migration->getSourceConfiguration()
    );

    $this->assertSame(
      [
        'id',
        'description',
        'menu_name',
        'new_nid',
        'link_path',
        'link/uri',
        'path_is_available',
        'stub',
        'title_suffix',
        'original_title',
        'title',
        'link/options',
        'route',
        'route_name',
        'route_parameters',
        'url',
        'options',
        'external',
        'weight',
        'expanded',
        'original_enabled',
        'enabled',
        'original_parent',
        'parent_uuid',
        'lookup_stub_parent',
        'parent',
        'changed',
      ],
      array_keys($node_translation_menu_links_migration->getProcess())
    );
  }

  /**
   * Test the enhanced menu link migration.
   */
  public function testMenuLinkMigration() {
    $fixture_path = implode(DIRECTORY_SEPARATOR, [
      dirname(__FILE__, 3),
      'fixtures',
      'd7-menu-link-db.php',
    ]);

    $this->loadFixture($fixture_path);

    $this->startCollectingMessages();
    $this->executeMigrations([
      'd7_node_type',
      'd7_user_role',
      'd7_user',
      'd7_menu',
      'd7_node_complete',
    ]);
    $this->assertExpectedMigrationMessages();
    $this->startCollectingMessages();
    $this->executeMigrations([
      'd7_menu_links',
    ]);
    $this->assertExpectedMigrationMessages();

    // There are 289 menu links in the source database which are picked up by
    // the menu link source plugin.
    //
    // There are 4 menu links whose path does not exists, but these are migrated
    // as well with destination path 'route:<current>':
    // - 820: The path "internal:/<void>" failed validation (dgo.to/void_menu).
    // - 1141: The path "internal:/<void1>" failed validation.
    // - 10046:The path "internal:/<void>" failed validation.
    // - 25951:The path "internal:/<void1>" failed validation.
    $expected_menu_links = 289;
    // 5 menu links are skipped:
    // - 216: Shortcut link (menu is missing).
    // - 217: Shortcut link (menu is missing).
    // - 996: Shortcut link (menu is missing).
    // - 1008: Shortcut link (menu is missing).
    // - 1513: Shortcut link (menu is missing).
    $expected_menu_links = $expected_menu_links - 5;
    // BUT! We have a menu link (mlid 681) in the trap migration: this was used
    // as a parent menu link, but it is completely missing from the source.
    $expected_menu_links = $expected_menu_links + 1;

    $this->assertCount($expected_menu_links, MenuLinkContent::loadMultiple());

    // Check the 'trapped' menu link content entity.
    $this->assertEquals(
      [
        'id' => [['value' => 681]],
        'enabled' => [['value' => 0]],
        'title' => [['value' => "mlid #681 (unavailable: 'MISSING')"]],
        'menu_name' => [['value' => 'main']],
        'link' => [
          [
            'uri' => 'route:<current>',
            'title' => NULL,
            'options' => [],
          ],
        ],
      ] + static::MENU_LINK_EXPECTATION_BASE,
      array_diff_key(
        MenuLinkContent::load(681)->toArray(),
        array_combine(static::IGNORED_PROPERTIES, static::IGNORED_PROPERTIES)
      )
    );

    // Check a menu link content entity which was migrated despite its missing
    // destination path.
    $this->assertEquals(
      [
        'id' => [['value' => 1141]],
        'enabled' => [['value' => 0]],
        'title' => [
          [
            'value' => "Menu link mlid #1141 (unavailable: '<void1>')",
          ],
        ],
        'menu_name' => [['value' => 'main']],
        'link' => [
          [
            'uri' => 'route:<current>',
            'title' => NULL,
            'options' => [
              'external' => TRUE,
            ],
          ],
        ],
        'weight' => [['value' => -50]],
        'parent' => [
          [
            'value' => 'menu_link_content:' . MenuLinkContent::load(15451)->uuid(),
          ],
        ],
      ] + static::MENU_LINK_EXPECTATION_BASE,
      array_diff_key(
        MenuLinkContent::load(1141)->toArray(),
        array_combine(static::IGNORED_PROPERTIES, static::IGNORED_PROPERTIES)
      )
    );
  }

  /**
   * Test the enhanced menu link migration with core's migrate DB fixture.
   *
   * @dataProvider providerTestWithCoreFixture
   */
  public function testWithCoreFixture(array $migrations_to_execute, array $mlid_20_source_data, array $expected_menu_link_data) {
    $this->prepareCoreTest();

    // Update the source of menu link 20 if necessary.
    if (!empty($mlid_20_source_data)) {
      $this->sourceDatabase->update('menu_links')
        ->condition('mlid', 20)
        ->fields($mlid_20_source_data)
        ->execute();
    }

    $this->startCollectingMessages();
    $this->executeMigrations([
      'language',
      'default_language',
      'd7_language_types',
      'd7_node_type',
      'd7_comment_type',
      'd7_user_role',
      'd7_user',
      'd7_menu',
      'd7_node_complete',
    ]);
    $this->assertExpectedMigrationMessages();
    $this->startCollectingMessages();
    $this->executeMigrations($migrations_to_execute);
    $this->assertExpectedMigrationMessages();

    $expected_menu_link_statuses = [
      245 => TRUE,
      467 => TRUE,
      468 => TRUE,
      469 => TRUE,
      470 => TRUE,
      478 => TRUE,
      479 => TRUE,
      484 => TRUE,
      // This points to a node translation URI (which isn't available if we
      // don't execute 'node_translation_menu_links').
      485 => FALSE,
      486 => TRUE,
      // This also points to a node translation URI.
      487 => FALSE,
    ];
    if (!empty($expected_menu_link_data[20])) {
      $expected_menu_link_statuses = [20 => NULL] + $expected_menu_link_statuses;
    }
    foreach ($expected_menu_link_data as $id => $data) {
      $expected_menu_link_statuses[$id] = (bool) $data['enabled'][0]['value'];
    }
    $menu_link_statuses_unordered = array_reduce(
      MenuLinkContent::loadMultiple(),
      function (array $carry, MenuLinkContentInterface $menu_link) {
        $carry[$menu_link->id()] = $menu_link->isEnabled();
        return $carry;
      },
      []
    );

    $menu_link_ids = array_keys($menu_link_statuses_unordered);
    ksort($menu_link_ids);
    foreach ($menu_link_ids as $id) {
      $actual_menu_link_statuses[$id] = $menu_link_statuses_unordered[$id];
    }

    $this->assertEquals(
      $expected_menu_link_statuses,
      $actual_menu_link_statuses
    );

    $actual_data = [];
    foreach (array_keys($expected_menu_link_data) as $mlid) {
      $actual_data[$mlid] = array_diff_key(
        MenuLinkContent::load($mlid)->toArray(),
        array_combine(static::IGNORED_PROPERTIES, static::IGNORED_PROPERTIES)
      );
    }
    $this->assertEquals($expected_menu_link_data, $actual_data);
  }

  /**
   * Data provider for ::testWithCoreFixture.
   *
   * @return array
   *   The test cases.
   */
  public static function providerTestWithCoreFixture(): array {
    return [
      'Menu link plugin available on destination (no trans)' => [
        'migrations_to_execute' => ['d7_menu_links'],
        'mlid_20_source_data' => [],
        'expected_menu_link_data' => [
          485 => [
            'id' => [['value' => 485]],
            'enabled' => [['value' => 0]],
            'title' => [
              ['value' => "is - The thing about Deep Space 9 (unavailable: 'node/3')"],
            ],
            'link' => [
              [
                'uri' => 'route:<current>',
                'title' => NULL,
                'options' => ['attributes' => ['title' => '']],
              ],
            ],
            'menu_name' => [['value' => 'tools']],
            'weight' => [['value' => 10]],
          ] + static::MENU_LINK_EXPECTATION_BASE,
          487 => [
            'id' => [['value' => 487]],
            'enabled' => [['value' => 0]],
            'title' => [
              ['value' => "en - The thing about Firefly (unavailable: 'node/5')"],
            ],
            'link' => [
              [
                'uri' => 'route:<current>',
                'title' => NULL,
                'options' => ['attributes' => ['title' => '']],
              ],
            ],
            'menu_name' => [['value' => 'tools']],
            'weight' => [['value' => 12]],
          ] + static::MENU_LINK_EXPECTATION_BASE,
        ],
      ],
      'Menu link plugin available on destination (with trans)' => [
        'migrations_to_execute' => [
          'd7_menu_links',
          'node_translation_menu_links',
        ],
        'mlid_20_source_data' => [],
        'expected_menu_link_data' => [
          485 => [
            'id' => [['value' => 485]],
            'enabled' => [['value' => 1]],
            'title' => [['value' => "is - The thing about Deep Space 9"]],
            'link' => [
              [
                'uri' => 'entity:node/2',
                'title' => NULL,
                'options' => ['attributes' => ['title' => '']],
              ],
            ],
            'menu_name' => [['value' => 'tools']],
            'weight' => [['value' => 10]],
          ] + static::MENU_LINK_EXPECTATION_BASE,
          487 => [
            'id' => [['value' => 487]],
            'enabled' => [['value' => 1]],
            'title' => [['value' => "en - The thing about Firefly"]],
            'link' => [
              [
                'uri' => 'entity:node/4',
                'title' => NULL,
                'options' => ['attributes' => ['title' => '']],
              ],
            ],
            'menu_name' => [['value' => 'tools']],
            'weight' => [['value' => 12]],
          ] + static::MENU_LINK_EXPECTATION_BASE,
        ],
      ],
      'Customized: Missing page and menu link on destination' => [
        'migrations_to_execute' => ['d7_menu_links'],
        'mlid_20_source_data' => [
          'link_path' => 'views/page',
          'link_title' => 'Custom views page',
          'options' => 'a:1:{s:10:"attributes";a:0:{}}',
          'weight' => 115,
        ],
        'expected_menu_link_data' => [
          20 => [
            'id' => [['value' => 20]],
            'enabled' => [['value' => 0]],
            'title' => [['value' => "Custom views page (unavailable: 'views/page')"]],
            'link' => [
              [
                'uri' => 'route:<current>',
                'title' => NULL,
                'options' => ['attributes' => []],
              ],
            ],
            'weight' => [['value' => 115]],
          ] + static::MENU_LINK_EXPECTATION_BASE,
        ],
      ],
      'Customized: Existing page' => [
        'migrations_to_execute' => ['d7_menu_links'],
        'mlid_20_source_data' => [
          'link_path' => 'contact',
          'link_title' => 'Contact us!',
          'options' => 'a:1:{s:10:"attributes";a:0:{}}',
          'weight' => -10,
          'expanded' => 1,
        ],
        'expected_menu_link_data' => [
          20 => [
            'id' => [['value' => 20]],
            'enabled' => [['value' => 1]],
            'title' => [['value' => 'Contact us!']],
            'link' => [
              [
                'uri' => 'internal:/contact',
                'title' => NULL,
                'options' => ['attributes' => []],
              ],
            ],
            'expanded' => [['value' => 1]],
            'rediscover' => [['value' => 1]],
            'weight' => [['value' => -10]],
          ] + static::MENU_LINK_EXPECTATION_BASE,
        ],
      ],
    ];
  }

  /**
   * Prepares environment for testing menu link migrations with core fixture.
   */
  protected function prepareCoreTest(): void {
    $this->enableModules([
      'config_translation',
      'locale',
      'language',
      'content_translation',
      'menu_ui',
      'text',
    ]);
    $this->installConfig(['comment', 'node']);
    $this->installSchema(
      'locale',
      ['locales_source', 'locales_target', 'locales_location']
    );

    $this->loadFixture(implode(DIRECTORY_SEPARATOR, [
      DRUPAL_ROOT,
      'core',
      'modules',
      'migrate_drupal',
      'tests',
      'fixtures',
      'drupal7.php',
    ]));

    // Simplify and speed up the test: we do not need any fields to be migrated.
    $this->sourceDatabase->delete('field_config')
      ->execute();
    $this->sourceDatabase->delete('field_config_instance')
      ->execute();
  }

}
