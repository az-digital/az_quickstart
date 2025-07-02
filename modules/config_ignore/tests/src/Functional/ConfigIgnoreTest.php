<?php

namespace Drupal\Tests\config_ignore\Functional;

use Drupal\config_test\ConfigTestInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Test functionality of config_ignore module.
 *
 * @group config_ignore
 */
class ConfigIgnoreTest extends ConfigIgnoreBrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config',
    'config_test',
  ];

  /**
   * Verify that the settings form works.
   */
  public function testSettingsForm() {
    // Login with a user that has permission to import config.
    $this->drupalLogin($this->drupalCreateUser(['import configuration']));

    $edit = [
      'ignored_config_entities' => 'config.test_01' . "\r\n" . 'config.test_02',
    ];

    $this->drupalGet('admin/config/development/configuration/ignore');
    $this->submitForm($edit, (string) $this->t('Save configuration'));

    $settings = $this->config('config_ignore.settings')->get('ignored_config_entities');

    $this->assertEquals(['config.test_01', 'config.test_02'], $settings);
  }

  /**
   * Verify that the config sync form loads after ignore settings are saved.
   *
   * Also installs core field module.
   */
  public function testSynchronizeForm() {
    $this->container->get('module_installer')->install(['field']);
    $this->drupalLogin($this->drupalCreateUser(['import configuration', 'synchronize configuration']));
    $edit = [
      'ignored_config_entities' => "system.site" . "\r\n" . "'system.menu.*'",
    ];
    $this->drupalGet('admin/config/development/configuration/ignore');
    $this->submitForm($edit, (string) $this->t('Save configuration'));
    $this->drupalGet('admin/config/development/configuration');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Verify that config can get ignored.
   */
  public function testValidateIgnoring() {

    // Set the site name to a known value that we later will try and overwrite.
    $this->config('system.site')->set('name', 'Test import')->save();

    // Set the system.site:name to be ignored upon config import.
    $this->config('config_ignore.settings')->set('ignored_config_entities', ['system.site'])->save();

    $this->doExport();

    // Change the site name, perform an import and see if the site name remains
    // the same, as it should.
    $this->config('system.site')->set('name', 'Changed title')->save();
    $this->doImport();
    $this->assertEquals('Changed title', $this->config('system.site')->get('name'));

  }

  /**
   * Verify all wildcard asterisk is working.
   */
  public function testValidateIgnoringWithWildcard() {

    // Set the site name to a known value that we later will try and overwrite.
    $this->config('system.site')->set('name', 'Test import')->save();

    // Set the system.site:name to be ignored upon config import.
    $this->config('config_ignore.settings')->set('ignored_config_entities', ['system.*'])->save();

    $this->doExport();

    // Change the site name, perform an import and see if the site name remains
    // the same, as it should.
    $this->config('system.site')->set('name', 'Changed title')->save();
    $this->doImport();
    $this->assertEquals('Changed title', $this->config('system.site')->get('name'));

  }

  /**
   * Verify Force Import syntax is working.
   *
   * This test makes sure we avoid regression issues.
   */
  public function testValidateForceImporting() {
    // Set the site name to a known value that we later will try and overwrite.
    $this->config('system.site')->set('name', 'Test import')->save();

    // Set the system.site:name to be (force-) imported upon config import.
    $settings = ['~system.site'];
    $this->config('config_ignore.settings')->set('ignored_config_entities', $settings)->save();

    $this->doExport();

    // Change the site name, perform an import and see if the site name remains
    // the same, as it should.
    $this->config('system.site')->set('name', 'Changed title')->save();
    $this->doImport();
    $this->assertEquals('Test import', $this->config('system.site')->get('name'));
  }

  /**
   * Verify excluded configuration works with wildcards.
   *
   * This test cover the scenario where a wildcard matches a specific
   * configuration, but that's still imported due exclusion.
   */
  public function testValidateForceImportingWithWildcard() {

    // Set the site name to a known value that we later will try and overwrite.
    $this->config('system.site')->set('name', 'Test import')->save();

    // Set the system.site:name to be (force-) imported upon config import.
    $settings = ['system.*', '~system.site'];
    $this->config('config_ignore.settings')->set('ignored_config_entities', $settings)->save();

    $this->doExport();

    // Change the site name, perform an import and see if the site name remains
    // the same, as it should.
    $this->config('system.site')->set('name', 'Changed title')->save();
    $this->doImport();
    $this->assertEquals('Test import', $this->config('system.site')->get('name'));

  }

  /**
   * Verify ignoring only some config keys.
   *
   * This test covers the scenario when not the whole config is to be ignored
   * but only a certain subset of it.
   */
  public function testValidateImportingWithIgnoredSubKeys() {

    // Set the site name to a known value that we later will try and overwrite.
    $this->config('system.site')
      ->set('name', 'Test name')
      ->set('slogan', 'Test slogan')
      ->set('page.front', '/ignore')
      ->save();

    // Set the system.site:name to be (force-) imported upon config import.
    $settings = ['system.site:name', 'system.site:page.front'];
    $this->config('config_ignore.settings')->set('ignored_config_entities', $settings)->save();

    $this->doExport();

    // Change the site name, perform an import and see if the site name remains
    // the same, as it should.
    $this->config('system.site')
      ->set('name', 'Changed title')
      ->set('slogan', 'Changed slogan')
      ->set('page.front', '/new-ignore')
      ->save();

    $this->doImport();
    $this->assertEquals('Changed title', $this->config('system.site')->get('name'));
    $this->assertEquals('Test slogan', $this->config('system.site')->get('slogan'));
    $this->assertEquals('/new-ignore', $this->config('system.site')->get('page.front'));
  }

  /**
   * Tests config in active storage is not deleted if it should be ignored.
   */
  public function testImportMissingConfig() {
    // Ignore a config entity.
    $this->config('config_ignore.settings')->set('ignored_config_entities', ['config_test.*'])->save();

    // Export the current state.
    $this->doExport();

    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $config_test_storage */
    $config_test_storage = $this->container->get('entity_type.manager')->getStorage('config_test');

    /** @var \Drupal\config_test\ConfigTestInterface $entity */
    $entity = $config_test_storage->create([
      'id' => 'foo',
      'label' => 'Foo',
    ]);
    $entity->save();

    $this->doImport();

    $loaded_entity = $config_test_storage->load($entity->id());
    $this->assertInstanceOf(ConfigTestInterface::class, $loaded_entity);
  }

}
