<?php

namespace Drupal\Tests\config_split\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test the config split status override service.
 *
 * @group config_split
 */
class StatusOverrideTest extends KernelTestBase {

  use SplitTestTrait;

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  protected static $modules = [
    'config_split',
  ];

  /**
   * Test the status override service.
   */
  public function testStateConfigOverride() {
    $this->createSplitConfig('active', ['status' => TRUE])->save();
    $this->createSplitConfig('inactive', ['status' => FALSE])->save();

    /** @var \Drupal\config_split\Config\StatusOverride $override */
    $override = $this->container->get('config_split.status_override');
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $configFactory */
    $configFactory = $this->container->get('config.factory');

    self::assertNull($override->getSplitOverride('active'));
    self::assertNull($override->getSplitOverride('inactive'));
    self::assertTrue($configFactory->get('config_split.config_split.active')->get('status'));
    self::assertFalse($configFactory->get('config_split.config_split.inactive')->get('status'));

    $override->setSplitOverride('active', FALSE);
    $override->setSplitOverride('inactive', TRUE);
    $configFactory->clearStaticCache();

    self::assertFalse($override->getSplitOverride('active'));
    self::assertFalse($configFactory->get('config_split.config_split.active')->get('status'));
    self::assertTrue($override->getSplitOverride('inactive'));
    self::assertTrue($configFactory->get('config_split.config_split.inactive')->get('status'));

    $override->setSplitOverride('active', NULL);
    $override->setSplitOverride('inactive', NULL);
    $configFactory->clearStaticCache();

    self::assertNull($override->getSplitOverride('active'));
    self::assertNull($override->getSplitOverride('inactive'));
    self::assertTrue($configFactory->get('config_split.config_split.active')->get('status'));
    self::assertFalse($configFactory->get('config_split.config_split.inactive')->get('status'));
  }

  /**
   * Test the settings.php overrides.
   */
  public function testGlobalOverride() {
    $config['config_split.config_split.test1']['status'] = TRUE;
    $config['config_split.config_split.test2']['status'] = FALSE;
    $GLOBALS['config'] = $config;

    /** @var \Drupal\config_split\Config\StatusOverride $override */
    $override = $this->container->get('config_split.status_override');
    self::assertTrue($override->getSettingsOverride('test1'));
    self::assertFalse($override->getSettingsOverride('test2'));
    self::assertNull($override->getSettingsOverride('test3'));
  }

}
