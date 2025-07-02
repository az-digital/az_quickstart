<?php

declare(strict_types=1);

namespace Drupal\migmag_menu_link_migrate;

use Drupal\Component\Utility\Unicode;
use Drupal\migmag\Utility\MigMagArrayUtility;
use Drupal\migmag\Utility\MigMagMigrationUtility;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

// cspell:ignore plid

/**
 * Alters and prepares menu link migrations to migrate as much data as possible.
 */
class MigMagMenuLinkMigrate {

  /**
   * The plugin ID of the menu link trap migration.
   *
   * @const string
   */
  const TRAP_MIGRATION_ID = 'migmag_unmigratable_menu_link_trap';

  /**
   * The path set for unmigratable (or stub) menu links.
   *
   * @const string
   */
  const NON_MIGRATABLE_LINKS_PATH = 'route:<current>';

  /**
   * Adds fixes to menu link migrations.
   *
   * @param array $migrations
   *   An array of migration configurations keyed by migration ID. See
   *   hook_migration_plugins_alter().
   * @param bool $migrate_as_much_as_possible
   *   Whether link/uri validation exceptions also should be suppressed.
   */
  public static function applyMenuLinkMigrationConfigurationFixes(array &$migrations, bool $migrate_as_much_as_possible = FALSE): void {
    $menu_link_migrations = array_filter(
      $migrations,
      function (array $definition) {
        return in_array(
          $definition['id'],
          [
            'd7_menu_links',
            'node_translation_menu_links',
          ]
        );
      }
    );

    foreach ($menu_link_migrations as $plugin_id => $plugin_definition) {
      if ($migrate_as_much_as_possible) {
        // If we want to migrate as much data as possible, we will wrap the
        // whole 'link/uri' process pipeline into migmag_try.
        $plugin_definition['process']['link/uri'] = [
          'plugin' => 'migmag_try',
          'process' => $plugin_definition['process']['link/uri'],
          'catch' => ['Drupal\migrate\MigrateException' => self::NON_MIGRATABLE_LINKS_PATH],
        ];

        // Ensure that 'enabled' and 'title' follow 'link/uri'.
        MigMagArrayUtility::moveAfterKey(
          $plugin_definition['process'],
          'link/uri',
          'enabled'
        );
        MigMagArrayUtility::moveAfterKey(
          $plugin_definition['process'],
          'link/uri',
          'title'
        );

        // ...so we can use the processed value of 'path_is_available' in the
        // process pipeline of 'title' and 'enabled'! 'path_is_available'
        // determines whether the migrated link path exists on the destination.
        MigMagArrayUtility::insertAfterKey(
          $plugin_definition['process'],
          'link/uri',
          'path_is_available',
          [
            'plugin' => 'static_map',
            'source' => '@link/uri',
            'map' => [self::NON_MIGRATABLE_LINKS_PATH => 0],
            'default_value' => 1,
          ]
        );

        // We add the original 'enabled' process as 'original_enabled' in front
        // of 'enabled', and add call 'intval' on its result: we want to use
        // this later in a static map.
        MigMagArrayUtility::insertInFrontOfKey(
          $plugin_definition['process'],
          'enabled',
          'original_enabled',
          array_merge(
            MigMagMigrationUtility::getAssociativeMigrationProcess($plugin_definition['process']['enabled']),
           [['plugin' => 'callback', 'callable' => 'intval']]
          )
        );

        // ...then we change the process pipeline of 'enabled': If we are about
        // to migrate a menu link whose destination path is missing then the
        // menu link should be disabled.
        $plugin_definition['process']['enabled'] = [
          'plugin' => 'static_map',
          'source' => [
            '@path_is_available',
            '@original_enabled',
          ],
          'map' => [
            1 => [
              0 => 0,
              1 => 1,
            ],
          ],
          'default_value' => 0,
        ];

        // To help our users, we also add a suffix to the menu link's title
        // which will contain the original menu link path.
        // First add the constants we will use.
        $plugin_definition['source']['constants']['missing_path_prefix'] = " (unavailable: '";
        $plugin_definition['source']['constants']['missing_path_suffix'] = "')";
        // Add the pipeline of 'title_suffix' which will contain the path in the
        // source if the link does not exist on the destination. It will be NULL
        // otherwise.
        MigMagArrayUtility::insertInFrontOfKey(
          $plugin_definition['process'],
          'title',
          'title_suffix',
          [
            [
              'plugin' => 'static_map',
              'source' => ['@path_is_available', '@stub'],
              'map' => [
                0 => ['do not skip'],
              ],
              'default_value' => NULL,
            ],
            [
              'plugin' => 'skip_on_empty',
              'method' => 'process',
            ],
            [
              'plugin' => 'concat',
              'source' => [
                'constants/missing_path_prefix',
                'link_path',
                'constants/missing_path_suffix',
              ],
            ],
          ]
        );
        MigMagArrayUtility::insertInFrontOfKey(
          $plugin_definition['process'],
          'title_suffix',
          'stub',
          [
            'plugin' => 'default_value',
            'source' => 'stub',
            'default_value' => 0,
          ]
        );
        // Re-add the original process pipeline of 'title' as 'original_title',
        // in front of 'title'.
        MigMagArrayUtility::insertInFrontOfKey(
          $plugin_definition['process'],
          'title',
          'original_title',
          $plugin_definition['process']['title']
        );
        // Finally, concatenate the processed value of 'original_title' and
        // 'title_suffix'!
        $plugin_definition['process']['title'] = [
          'plugin' => 'concat',
          'source' => [
            '@original_title',
            '@title_suffix',
          ],
        ];
      }

      // Add the required constant for 'lookup_stub_parent'.
      $plugin_definition['source']['constants']['plugin_prefix'] = 'menu_link_content';
      MigMagArrayUtility::insertInFrontOfKey(
        $plugin_definition['process'],
        'parent',
        'lookup_stub_parent',
        [
          [
            'plugin' => 'skip_on_empty',
            'source' => '@parent_uuid',
            'method' => 'process',
          ],
          [
            'plugin' => 'concat',
            'source' => [
              'constants/plugin_prefix',
              '@parent_uuid',
            ],
            'delimiter' => ':',
          ],
        ]
      );

      // Add the 'parent_uuid' process right before the new 'parent' process
      // pipeline.
      MigMagArrayUtility::insertInFrontOfKey(
        $plugin_definition['process'],
        'lookup_stub_parent',
        'parent_uuid',
        [
          [
            'plugin' => 'callback',
            'callable' => 'is_string',
            'source' => '@original_parent',
          ],
          [
            'plugin' => 'callback',
            'callable' => 'intval',
          ],
          [
            'plugin' => 'static_map',
            'map' => [1, 0],
          ],
          [
            'plugin' => 'skip_on_empty',
            'method' => 'process',
          ],
          [
            'plugin' => 'skip_on_empty',
            'source' => 'plid',
            'method' => 'process',
          ],
          [
            'plugin' => 'migmag_lookup',
            'stub_default_values' => [
              'menu_name' => '@menu_name',
            ],
            'migration' => [
              'd7_menu_links',
              'd7_menu_links_localized',
              'd7_menu_links_translation',
              'node_translation_menu_links',
            ],
            'fallback_stub_id' => static::TRAP_MIGRATION_ID,
          ],
          [
            'plugin' => 'skip_on_empty',
            'method' => 'process',
          ],
          [
            'plugin' => 'migmag_get_entity_property',
            'entity_type_id' => 'menu_link_content',
            'property' => 'uuid',
          ],
        ]
      );

      // We need the original parent process pipeline because it also searches
      // for parents based on the parent menu link's path. But we will use
      // migmag_try to catch its exceptions.
      MigMagArrayUtility::insertInFrontOfKey(
        $plugin_definition['process'],
        'parent_uuid',
        'original_parent',
        [
          [
            'plugin' => 'migmag_try',
            'process' => $plugin_definition['process']['parent'],
            'catch' => ['Drupal\migrate\MigrateSkipRowException' => NULL],
            'saveMessage' => FALSE,
          ],
          [
            'plugin' => 'default_value',
            'default_value' => NULL,
          ],
        ]
      );

      // Change the parent process to use null coalesce.
      $plugin_definition['process']['parent'] = [
        'plugin' => 'null_coalesce',
        'source' => [
          '@original_parent',
          '@lookup_stub_parent',
        ],
      ];

      $migrations[$plugin_id] = $plugin_definition;
    }
  }

  /**
   * Prepares the migration of menu link stubs.
   *
   * @param \Drupal\migrate\Row $row
   *   The row being imported.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The current migration.
   */
  public static function prepareMenuLinkStubMigration(Row $row, MigrationInterface $migration): void {
    if (
      !$migration->getDestinationPlugin()->getPluginId() === 'entity:menu_link_content' ||
      !$row->isStub()
    ) {
      return;
    }

    // Do not process plid, link_path for menu link content stubs.
    $source_path = $row->getSourceProperty('link_path');
    $title_suffix = $source_path
      ? " (stub: '{$source_path}')"
      : '';
    $row->setSourceProperty(
      'link_title',
      $row->getSourceProperty('link_title') . $title_suffix
    );
    $row->setSourceProperty('stub', 1);
    $row->setSourceProperty('plid', 0);
    $row->setSourceProperty('link_path', 'route:<current>');
    // Stubs must be disabled.
    $row->setSourceProperty('enabled', FALSE);
  }

  /**
   * Returns the link path of the specified menu link.
   *
   * @param string|int $mlid
   *   The menu link identifier in the source Drupal 7 instance.
   *
   * @return array|null
   *   The crucial data of the specified menu link, or NULL if the menu link
   *   cannot be found in the source.
   */
  public static function getSourceMenuLinkData($mlid): ?array {
    // We need a Drupal Sql based source plugin.
    // @see MigrationDeriverTrait::getSourcePlugin()
    $manager = \Drupal::service('plugin.manager.migration');
    assert($manager instanceof MigrationPluginManagerInterface);
    $variable = $manager->createStubMigration([
      'source' => ['ignore_map' => TRUE, 'plugin' => 'variable'],
      'destination' => ['plugin' => 'null'],
      'idMap' => ['plugin' => 'null'],
    ])->getSourcePlugin();
    assert($variable instanceof DrupalSqlBase);

    $menu_link_path = $variable->getDatabase()
      ->select('menu_links', 'ml')
      ->fields('ml', [
        'link_title',
        'link_path',
        'options',
        'weight',
        'hidden',
        'expanded',
      ])
      ->condition('ml.mlid', $mlid)
      ->execute()
      ->fetch(\PDO::FETCH_ASSOC);

    if (!$menu_link_path) {
      return NULL;
    }

    // See \Drupal\menu_link_content\Plugin\migrate\source::prepareRow()
    $menu_link_path['enabled'] = (int) !$menu_link_path['hidden'];
    $menu_link_path['options'] = unserialize($menu_link_path['options']);
    $menu_link_path['description'] = Unicode::truncate($menu_link_path['options']['attributes']['title'] ?? '', 255);

    return $menu_link_path;
  }

}
