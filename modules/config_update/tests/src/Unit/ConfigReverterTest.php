<?php

namespace Drupal\Tests\config_update\Unit;

use Drupal\config_update\ConfigDeleteInterface;
use Drupal\config_update\ConfigReverter;
use Drupal\config_update\ConfigRevertInterface;

/**
 * Tests the \Drupal\config_update\ConfigReverter class.
 *
 * @group config_update
 *
 * @coversDefaultClass \Drupal\config_update\ConfigReverter
 */
class ConfigReverterTest extends ConfigUpdateUnitTestBase {

  /**
   * The config reverter to test.
   *
   * @var \Drupal\config_update\ConfigReverter
   */
  protected $configReverter;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->configReverter = new ConfigReverter(
      $this->getEntityManagerMock(),
      $this->getConfigStorageMock('active'),
      $this->getConfigStorageMock('extension'),
      $this->getConfigStorageMock('optional'),
      $this->getConfigFactoryMock(),
      $this->getEventDispatcherMock());
  }

  /**
   * @covers \Drupal\config_update\ConfigReverter::getFromActive
   * @dataProvider getFromActiveProvider
   */
  public function testGetFromActive($a, $b, $expected) {
    $this->assertEquals($expected, $this->configReverter->getFromActive($a, $b));
  }

  /**
   * Data provider for self:testGetFromActive().
   */
  public function getFromActiveProvider() {
    return [
      // Arguments are $type, $name, and return value is the config.
      // Some config items that are already prefixed.
      ['', 'foo.bar.one', ['foo.bar.one' => 'active', 'id' => 'one']],
      ['system.simple', 'foo.bar.one',
        ['foo.bar.one' => 'active', 'id' => 'one'],
      ],
      // Config item with a defined entity definition prefix. Entity type 'foo'
      // has prefix 'foo.bar'.
      ['foo', 'one', ['foo.bar.one' => 'active', 'id' => 'one']],
      // Unknown type. This should not generate a call into the config read,
      // so should not return the known value.
      ['unknown', 'foo.bar.one', FALSE],
      // Missing configuration. Config mock is configured to return FALSE for
      // this particular config name.
      ['system.simple', 'missing', FALSE],
    ];
  }

  /**
   * @covers \Drupal\config_update\ConfigReverter::getFromExtension
   * @dataProvider getFromExtensionProvider
   */
  public function testGetFromExtension($a, $b, $expected) {
    $this->assertEquals($expected, $this->configReverter->getFromExtension($a, $b));
  }

  /**
   * Data provider for self:testGetFromExtension().
   */
  public function getFromExtensionProvider() {
    return [
      // Arguments are $type, $name, and return value is the config.
      // Some config items that are already prefixed, and exist in the mock
      // extension storage.
      ['', 'in.extension', ['in.extension' => 'extension']],
      ['system.simple', 'in.extension', ['in.extension' => 'extension']],
      // Config item with a defined entity definition prefix. Entity type 'foo'
      // has prefix 'foo.bar'.
      ['foo', 'one', ['foo.bar.one' => 'extension', 'id' => 'one']],
      // One that exists in both extension and optional storage.
      ['system.simple', 'in.both', ['in.both' => 'extension']],
      // One that exists only in optional storage.
      ['system.simple', 'in.optional', ['in.optional' => 'optional']],
      // Unknown type. This should not generate a call into the config read,
      // so should not return the known value.
      ['unknown', 'in.extension', FALSE],
      // Missing configuration. Storage mock is configured to return FALSE for
      // this particular config name.
      ['system.simple', 'missing2', FALSE],
    ];
  }

  /**
   * @covers \Drupal\config_update\ConfigReverter::import
   * @dataProvider importProvider
   */
  public function testImport($type, $name, $config_name, $expected, $config_before, $config_after) {
    // Clear dispatch log and set pre-config.
    $this->dispatchedEvents = [];
    if ($config_name) {
      $this->configStorage[$config_name] = $config_before;
    }
    $save_config = $this->configStorage;

    // Call the importer and test the Boolean result.
    $result = $this->configReverter->import($type, $name);
    $this->assertEquals($expected, $result);

    if ($result) {
      // Verify that the config is correct after import, and logging worked.
      $this->assertEquals($config_after, $this->configStorage[$config_name]);
      $this->assertCount(2, $this->dispatchedEvents);
      $this->assertEquals(ConfigRevertInterface::PRE_IMPORT, $this->dispatchedEvents[0][0]);
      $this->assertEquals(ConfigRevertInterface::IMPORT, $this->dispatchedEvents[1][0]);
    }
    else {
      // Verify that the config didn't change and no events were logged.
      $this->assertEquals($save_config, $this->configStorage);
      $this->assertCount(0, $this->dispatchedEvents);
    }
  }

  /**
   * Data provider for self:testImport().
   */
  public function importProvider() {
    return [
      // Elements: type, name, config name, return value,
      // config to set up before, config expected after. See also
      // getFromExtensionProvider().
      [
        'system.simple',
        'in.extension',
        'in.extension',
        TRUE,
        ['in.extension' => 'before'],
        ['in.extension' => 'extension', '_core' => 'core_for_in.extension'],
      ],
      [
        'foo',
        'one',
        'foo.bar.one',
        TRUE,
        ['foo.bar.one' => 'before', 'id' => 'one'],
        [
          'foo.bar.one' => 'extension',
          'id' => 'one',
          '_core' => 'core_for_foo.bar.one',
        ],
      ],
      [
        'system.simple',
        'in.both',
        'in.both',
        TRUE,
        ['in.both' => 'before'],
        ['in.both' => 'extension', '_core' => 'core_for_in.both'],
      ],
      [
        'system.simple',
        'in.optional',
        'in.optional',
        TRUE,
        ['in.optional' => 'before'],
        ['in.optional' => 'optional', '_core' => 'core_for_in.optional'],
      ],
      // Will be altered if the extension config exists.
      [
        'system.simple',
        'in.extension.pre_import',
        'in.extension.pre_import',
        TRUE,
        ['prop' => 'unaltered_value'],
        [
          'prop' => 'altered_value',
          'new_prop' => 'new_value',
          '_core' => 'core_for_in.extension.pre_import',
        ],
      ],
      // Will not be altered if the extension config doesn't exist.
      [
        'system.simple',
        'missing2,pre_import',
        'missing2.pre_import',
        FALSE,
        FALSE,
        FALSE,
      ],
      [
        'unknown',
        'in.extension',
        FALSE,
        FALSE,
        FALSE,
        FALSE,
      ],
      [
        'system.simple',
        'missing2',
        'missing2',
        FALSE,
        FALSE,
        FALSE,
      ],
    ];
  }

  /**
   * @covers \Drupal\config_update\ConfigReverter::revert
   * @dataProvider revertProvider
   */
  public function testRevert($type, $name, $config_name, $expected, $config_before, $config_after) {
    // Clear dispatch log and set pre-config.
    $this->dispatchedEvents = [];
    if ($config_name) {
      $this->configStorage[$config_name] = $config_before;
    }
    $save_config = $this->configStorage;

    // Call the reverter and test the Boolean result.
    $result = $this->configReverter->revert($type, $name);
    $this->assertEquals($expected, $result);

    if ($result) {
      // Verify that the config is correct after revert, and logging worked.
      $this->assertEquals($config_after, $this->configStorage[$config_name]);
      $this->assertCount(2, $this->dispatchedEvents);
      $this->assertEquals(ConfigRevertInterface::PRE_REVERT, $this->dispatchedEvents[0][0]);
      $this->assertEquals(ConfigRevertInterface::REVERT, $this->dispatchedEvents[1][0]);
    }
    else {
      // Verify that the config didn't change and no events were logged.
      $this->assertEquals($save_config, $this->configStorage);
      $this->assertCount(0, $this->dispatchedEvents);
    }
  }

  /**
   * Data provider for self:testRevert().
   */
  public function revertProvider() {
    return [
      // Elements: type, name, config name, return value,
      // config to set up before, config expected after. See also
      // getFromExtensionProvider().
      // The active config's 'prop' property will not be reverted.
      [
        'system.simple',
        'in.extension.pre_revert',
        'in.extension.pre_revert',
        TRUE,
        ['prop' => 'unaltered_value'],
        [
          'prop' => 'active.pre_revert_value',
          'new_prop' => 'new_value',
          '_core' => 'core_for_in.extension.pre_revert',
        ],
      ],
      // The active config's 'prop' property will not be reverted.
      [
        'foo',
        'pre_revert',
        'foo.bar.pre_revert',
        TRUE,
        ['foo.bar.pre_revert' => 'active', 'id' => 'one'],
        [
          'foo.bar.pre_revert' => 'extension',
          'id' => 'pre_revert',
          'prop' => 'active.pre_revert_value',
          'new_prop' => 'new_value',
          '_core' => 'core_for_foo.bar.pre_revert',
        ],
      ],
      [
        'system.simple',
        'in.extension',
        'in.extension',
        TRUE,
        ['in.extension' => 'active'],
        ['in.extension' => 'extension', '_core' => 'core_for_in.extension'],
      ],
      [
        'foo',
        'one',
        'foo.bar.one',
        TRUE,
        ['foo.bar.one' => 'active', 'id' => 'one'],
        [
          'foo.bar.one' => 'extension',
          'id' => 'one',
          '_core' => 'core_for_foo.bar.one',
        ],
      ],
      [
        'system.simple',
        'in.both',
        'in.both',
        TRUE,
        ['in.both' => 'active'],
        ['in.both' => 'extension', '_core' => 'core_for_in.both'],
      ],
      [
        'system.simple',
        'in.optional',
        'in.optional',
        TRUE,
        ['in.optional' => 'active'],
        ['in.optional' => 'optional', '_core' => 'core_for_in.optional'],
      ],
      [
        'unknown',
        'in.extension',
        FALSE,
        FALSE,
        FALSE,
        FALSE,
      ],
      // Missing from extension storage.
      [
        'system.simple',
        'missing2',
        'missing2',
        FALSE,
        FALSE,
        FALSE,
      ],
      // Present in extension storage but missing from active storage.
      [
        'system.simple',
        'another',
        'another',
        FALSE,
        FALSE,
        FALSE,
      ],
    ];
  }

  /**
   * @covers \Drupal\config_update\ConfigReverter::delete
   * @dataProvider deleteProvider
   */
  public function testDelete($type, $name, $config_name, $expected, $config_before = NULL) {
    // Clear dispatch log.
    $this->dispatchedEvents = [];
    if ($config_name && $config_before) {
      $this->configStorage[$config_name] = $config_before;
    }
    $save_config = $this->configStorage;

    // Call the configReverter delete method and test the Boolean result.
    $result = $this->configReverter->delete($type, $name);
    $this->assertEquals($expected, $result);

    if ($result) {
      // Verify that the config is missing after delete, and logging worked.
      $this->assertNotTrue(isset($this->configStorage[$config_name]));
      $this->assertCount(2, $this->dispatchedEvents);
      $this->assertEquals(ConfigDeleteInterface::PRE_DELETE, $this->dispatchedEvents[0][0]);
      $this->assertEquals(ConfigDeleteInterface::DELETE, $this->dispatchedEvents[1][0]);
    }
    else {
      // Verify that the config didn't change and no events were logged.
      $this->assertEquals($save_config, $this->configStorage);
      $this->assertCount(0, $this->dispatchedEvents);
    }
  }

  /**
   * Data provider for self:testDelete().
   */
  public function deleteProvider() {
    return [
      // Elements: type, name, config name, return value,
      // config to set up before (optional).
      [
        'system.simple',
        'in.extension',
        'in.extension',
        TRUE,
      ],
      [
        'foo',
        'one',
        'foo.bar.one',
        TRUE,
        ['foo.bar.one' => 'before', 'id' => 'one'],
      ],
      [
        'unknown',
        'in.extension',
        FALSE,
        FALSE,
      ],
      [
        'system.simple',
        'missing2',
        'missing2',
        FALSE,
      ],
    ];
  }

}
