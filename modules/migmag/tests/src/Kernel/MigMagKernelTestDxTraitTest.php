<?php

namespace Drupal\Tests\migmag\Kernel;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ExtensionList;
use Drupal\Core\Extension\ThemeInstallerInterface;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\Tests\migmag\Traits\CoreCompatibilityTrait;
use Drupal\Tests\migmag\Traits\MigMagKernelTestDxTrait;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Tests MigMagKernelTestDxTrait.
 *
 * @coversDefaultClass \Drupal\Tests\migmag\Traits\MigMagKernelTestDxTrait
 *
 * @group migmag
 */
class MigMagKernelTestDxTraitTest extends MigrateDrupalTestBase {

  use MigMagKernelTestDxTrait;
  use UserCreationTrait;
  use CoreCompatibilityTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->enableAllCoreModules();

    $this->loadFixture(
      implode(DIRECTORY_SEPARATOR, [
        DRUPAL_ROOT,
        'core',
        'modules',
        'migrate_drupal',
        'tests',
        'fixtures',
        'drupal7.php',
      ])
    );

    $this->installSchema('ban', ['ban_ip']);
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installSchema('dblog', ['watchdog']);
    $this->installSchema('file', ['file_usage']);
    $this->installSchema('locale', [
      'locales_source',
      'locales_location',
      'locales_target',
    ]);
    $this->installSchema('node', ['node_access']);
    $this->installSchema('search', ['search_dataset']);
    $this->installSchema('shortcut', ['shortcut_set_users']);

    DeprecationHelper::backwardsCompatibleCall(
      \Drupal::VERSION,
      '10.2',
      function () {
        $this->installSchema('help', ['help_search_items']);
      },
      function () {
        $this->installSchema('help_topics', ['help_search_items']);
      }
    );
    DeprecationHelper::backwardsCompatibleCall(
      \Drupal::VERSION,
      '11.0',
      fn () => 0,
      function () {
        $this->installSchema('book', ['book']);
        $this->installSchema('forum', ['forum', 'forum_index']);
        $this->installSchema('statistics', ['node_counter']);
        $this->installSchema('tracker', ['tracker_node', 'tracker_user']);
      },
    );

    if (!static::coreAggregatorIsMissing()) {
      $this->installEntitySchema('aggregator_feed');
      $this->installEntitySchema('aggregator_item');
    }
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('file');
    $this->installEntitySchema('menu_link_content');
    $this->installEntitySchema('node');
    $this->installEntitySchema('path_alias');
    $this->installEntitySchema('shortcut');
    $this->installEntitySchema('taxonomy_term');

    $this->installConfig('block_content');
    $this->installConfig('node');
    $this->installConfig('comment');

    // Set up filesystem related stuffs.
    $site_path = $this->container->hasParameter('site.path')
      ? $this->container->getParameter('site.path')
      : $this->container->get('site.path');
    $this->setSetting('file_private_path', $site_path . '/private');

    // Install default and default admin themes.
    [$default_theme, $admin_theme] = static::claroAndOliveroAreDefaultThemes()
      ? ['olivero', 'claro']
      : ['bartik', 'seven'];
    $theme_installer = \Drupal::service('theme_installer');
    assert($theme_installer instanceof ThemeInstallerInterface);
    $theme_installer->install([$default_theme, $admin_theme]);
    $this->config('system.theme')
      ->set('default', $default_theme)
      ->set('admin', $admin_theme)
      ->save();

    // We have to create at least an anonymous user.
    // @see https://drupal.org/i/3056234#comment-13275077
    $anonymous = $this->createUser([], '', FALSE, [
      'uid' => 0,
      'langcode' => 'und',
    ]);
    $this->setCurrentUser($anonymous);
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    $container->register('stream_wrapper.private', PrivateStream::class)
      ->addTag('stream_wrapper', ['scheme' => 'private']);
  }

  /**
   * Tests Drupal core migrations executed with getOrderedDrupalMigrationIds.
   *
   * @covers ::getOrderedDrupalMigrationIds
   */
  public function testExecuteAllDrupal7Migrations(): void {
    $this->startCollectingMessages();
    $this->executeMigrations($this->getOrderedDrupalMigrationIds());

    // If there are any migration status messages, then we assume that we have
    // only one, which was triggered by the blocked IP migration:
    // https://drupal.org/i/3260391.
    if (!empty($this->migrateMessages['status'])) {
      $this->assertCount(1, $this->migrateMessages['status']);
      $this->assertSame(
        'New object was not saved, no error provided',
        (string) reset($this->migrateMessages['status'])
      );
    }

    // If there are any migration error messages, then all of them should be
    // because of https://drupal.org/i/3260352.
    $expected_error_count = DeprecationHelper::backwardsCompatibleCall(
      \Drupal::VERSION,
      '10.2',
      fn () => 4,
      fn () => 5,
    );
    if (!empty($this->migrateMessages['error'])) {
      if (count($this->migrateMessages['error']) !== $expected_error_count) {
        $this->assertExpectedMigrationMessages();
      }
      foreach ($this->migrateMessages['error'] as $message) {
        $this->assertMatchesRegularExpression(
          "/'language_content_settings' entity with ID '.*' already exists\./",
          (string) $message
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareMigration(MigrationInterface $migration) {
    $source = $migration->getSourceConfiguration();
    if ($source['plugin'] === 'd7_file') {
      $source_file_path = implode(
        DIRECTORY_SEPARATOR,
        [
          DRUPAL_ROOT,
          'core',
          'modules',
          'migrate_drupal_ui',
          'tests',
          'src',
          'Functional',
          'd7',
          'files',
        ]
      );

      $source['constants']['source_base_path'] = $source_file_path;
      $migration->set('source', $source);
    }
  }

  /**
   * Enables all (non-test) modules from core.
   */
  protected function enableAllCoreModules() {
    $extension_list = \Drupal::service('extension.list.module');
    assert($extension_list instanceof ExtensionList);
    $installed = $extension_list->getAllInstalledInfo();
    $available_extensions = $extension_list->reset()->getList();
    $uninstalled_extensions = array_diff_key($available_extensions, $installed);

    $needs_to_be_installed = array_filter(
      $uninstalled_extensions,
      function (Extension $extension) {
        return strpos($extension->getPathname(), 'core/modules/') === 0 &&
          !preg_match('/core\/modules\/\w*\/tests\//', $extension->getPathname()) &&
          empty($extension->info['core_incompatible']) &&
          (isset($extension->info['package']) && mb_strtolower($extension->info['package']) !== 'testing');
      }
    );

    if (static::coreAggregatorIsMissing()) {
      unset($needs_to_be_installed['aggregator']);
    }

    $this->enableModules(array_keys($needs_to_be_installed));
  }

}
