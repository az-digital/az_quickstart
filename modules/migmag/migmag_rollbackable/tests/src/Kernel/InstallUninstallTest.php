<?php

declare(strict_types=1);

namespace Drupal\Tests\migmag_rollbackable\Kernel;

use Drupal\Component\Utility\Random;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests how MigMag Rollbackable is uninstalled.
 */
class InstallUninstallTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'tecla',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->installSchema('user', ['users_data']);
  }

  /**
   * Tests how MigMag Rollbackable installs if migration module isn't around.
   */
  public function testInstall(): void {
    $moduleInstaller = \Drupal::service('module_installer');
    $this->assertInstanceOf(ModuleInstallerInterface::class, $moduleInstaller);
    // No exception expected.
    $moduleInstaller->install(['migmag_rollbackable']);
  }

  /**
   * Tests how MigMag Rollbackable uninstalls if migration module isn't around.
   */
  public function testUninstall(): void {
    $this->enableModules(['migmag_rollbackable']);
    $this->createTable('migmag_rollbackable_data');
    $this->createTable('migmag_rollbackable_new_targets');

    $moduleInstaller = \Drupal::service('module_installer');
    $this->assertInstanceOf(ModuleInstallerInterface::class, $moduleInstaller);
    // No exception expected.
    $moduleInstaller->uninstall(['migmag_rollbackable']);
  }

  /**
   * Creates a database table.
   *
   * @param string $table
   *   Name of the table.
   */
  protected function createTable(string $table): void {
    $key = (new Random())->word(7);
    \Drupal::database()->schema()->createTable($table, [
      'fields' => ["foo_$key" => ['type' => 'int', 'not null' => TRUE]],
      'primary key' => ["foo_$key"],
    ]);
  }

}
