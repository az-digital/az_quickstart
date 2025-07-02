<?php

declare(strict_types=1);

namespace Drupal\Tests\flag_bookmark\Kernel;

use Drupal\Tests\flag\Kernel\FlagKernelTestBase;
use Drupal\flag\Entity\Flag;
use Drupal\views\Entity\View;

/**
 * Tests that the Flag bookmark module can be installed and uninstalled.
 *
 * @group flag_bookmark
 */
class FlagBookmarkInstallUninstallTest extends FlagKernelTestBase {

  /**
   * Test Install Uninstall.
   */
  public function testInstallUninstall() {
    // Provides configuration depended on by the view.
    $this->installConfig(['system']);
    // Tables necessary for uninstall.
    $this->installSchema('user', ['users_data']);

    // Install the Flag bookmark module.
    $this->container->get('module_installer')->install(['flag_bookmark']);
    $this->doTestsOnInstall();

    // Uninstall the Flag bookmark module and ensure it cleans up.
    $this->container->get('module_installer')->uninstall(['flag_bookmark']);
    $this->assertNull(Flag::load('bookmark'));
    $this->assertNull(View::load('flag_bookmark'));

    // Install the Flag bookmark module again.
    $this->container->get('module_installer')->install(['flag_bookmark']);
    $this->doTestsOnInstall();
  }

  /**
   * Do Tests On Install.
   */
  protected function doTestsOnInstall() {
    $this->assertEquals(['node', 'flag_bookmark'], Flag::load('bookmark')->getDependencies()['module']);
    $this->assertEquals(['flag.flag.bookmark', 'system.menu.main'], View::load('flag_bookmark')->getDependencies()['config']);
  }

}
