<?php

declare(strict_types=1);

namespace Drupal\Tests\flag_follower\Kernel;

use Drupal\Tests\flag\Kernel\FlagKernelTestBase;
use Drupal\flag\Entity\Flag;
use Drupal\views\Entity\View;

/**
 * Tests that the Flag follower module can be installed and uninstalled.
 *
 * @group flag_follower
 */
class FlagFollowerInstallUninstallTest extends FlagKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field', 'text'];

  /**
   * Test Install Uninstall.
   */
  public function testInstallUninstall() {
    // Provides configuration depended on by the view.
    $this->installConfig(['node']);
    // Tables necessary for uninstall.
    $this->installSchema('user', ['users_data']);

    // Install the Flag follower module.
    $this->container->get('module_installer')->install(['flag_follower']);
    $this->doTestsOnInstall();

    // Uninstall the Flag follower module and ensure it cleans up.
    $this->container->get('module_installer')->uninstall(['flag_follower']);
    $this->assertNull(Flag::load('bookmark'));
    $this->assertNull(View::load('flag_bookmark'));

    // Install the Flag bookmark module again.
    $this->container->get('module_installer')->install(['flag_follower']);
    $this->doTestsOnInstall();
  }

  /**
   * Do Tests On Install.
   */
  protected function doTestsOnInstall() {
    $this->assertEquals(['user', 'flag_follower'], Flag::load('following')->getDependencies()['module']);
    $this->assertEquals(['flag.flag.following'], View::load('flag_followers')->getDependencies()['config']);
    $this->assertEquals(['core.entity_view_mode.node.full', 'flag.flag.following'], View::load('flag_followers_content')->getDependencies()['config']);
  }

}
