<?php

namespace Drupal\Tests\config_split\Kernel;

use Drupal\Core\Config\MemoryStorage;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\config_filter\Kernel\ConfigStorageTestTrait;

/**
 * Test active and inactive splits.
 *
 * @group config_split
 */
class InactiveSplitTest extends KernelTestBase {

  use ConfigStorageTestTrait;
  use SplitTestTrait;

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  protected static $modules = [
    'system',
    'config_split',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['system']);
  }

  /**
   * Test.
   */
  public function testActiveInactive() {
    // Simple split with default configuration.
    $account = $this->createSplitConfig('account', [
      'complete_list' => ['system.menu.account'],
      'status' => TRUE,
    ]);

    $admin = $this->createSplitConfig('admin', [
      'complete_list' => ['system.menu.admin'],
      'status' => FALSE,
    ]);

    $active = $this->getActiveStorage();
    $expectedExport = new MemoryStorage();
    $expectedAccount = new MemoryStorage();
    $expectedAdmin = new MemoryStorage();

    // Set up expectations.
    foreach ($active->listAll() as $name) {
      $data = $active->read($name);
      if ($name === 'system.menu.account') {
        $expectedAccount->write($name, $data);
      }
      else {
        $expectedExport->write($name, $data);
      }
    }

    $export = $this->getExportStorage();
    static::assertStorageEquals($expectedExport, $export);
    static::assertStorageEquals($expectedAccount, $this->getSplitPreviewStorage($account, $export));
    static::assertStorageEquals($expectedAdmin, $this->getSplitPreviewStorage($admin, $export));

    // Write the export to the file system and assert the import to work.
    $this->copyConfig($expectedExport, $this->getSyncFileStorage());
    $this->copyConfig($expectedAccount, $this->getSplitSourceStorage($account));
    $this->copyConfig($expectedAdmin, $this->getSplitSourceStorage($admin));
    static::assertStorageEquals($active, $this->getImportStorage());

    // Override the status of the split.
    $GLOBALS['config'][$account->getName()]['status'] = FALSE;
    $GLOBALS['config'][$admin->getName()]['status'] = TRUE;
    $this->container->get('config.factory')->clearStaticCache();

    $expectedAdmin->write('system.menu.admin', $active->read('system.menu.admin'));
    $expectedExport->delete('system.menu.admin');
    $expectedExport->write('system.menu.account', $active->read('system.menu.account'));

    $export = $this->getExportStorage();
    static::assertStorageEquals($expectedExport, $export);
    static::assertStorageEquals($expectedAccount, $this->getSplitPreviewStorage($account, $export));
    static::assertStorageEquals($expectedAdmin, $this->getSplitPreviewStorage($admin, $export));

    $expectedImport = new MemoryStorage();
    $this->copyConfig($active, $expectedImport);
    // We exported for real only before the config override, now the account
    // split is turned off.
    $expectedImport->delete('system.menu.account');
    static::assertStorageEquals($expectedImport, $this->getImportStorage());
  }

}
