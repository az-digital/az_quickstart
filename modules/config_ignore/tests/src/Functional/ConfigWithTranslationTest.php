<?php

namespace Drupal\Tests\config_ignore\Functional;

use Drupal\Component\FileCache\FileCache;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;
use Drush\TestTraits\DrushTestTrait;

/**
 * Tests config_ignore with translated configurations.
 *
 * This test is using Drush to perform the export/import operations in order to
 * test with a real config import/export tool.
 *
 * @group config_ignore
 */
class ConfigWithTranslationTest extends BrowserTestBase {

  use DrushTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config_ignore',
    'config_translation',
    'language',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    ConfigurableLanguage::createFromLangcode('ro')->save();

    // Add a translation to user.role.anonymous and user.role.authenticated.
    $this->translateConfig('user.role.anonymous', 'label', 'Utilizator anonim', 'ro');
    $this->translateConfig('user.role.authenticated', 'label', 'Utilizator autentificat', 'ro');

    // Export the default configuration.
    $this->drush('config:export', [], ['yes' => NULL]);

    // Check that configs (and translations) were exported in the sync store.
    $this->assertExportedValue('user.settings', 'anonymous', 'Anonymous');
    $this->assertExportedValue('user.role.anonymous', 'label', 'Anonymous user');
    $this->assertExportedValue('user.role.anonymous', 'label', 'Utilizator anonim', 'ro');
    $this->assertExportedValue('user.role.authenticated', 'label', 'Authenticated user');
    $this->assertExportedValue('user.role.authenticated', 'weight', 1);
    $this->assertExportedValue('user.role.authenticated', 'is_admin', FALSE);
    $this->assertExportedValue('user.role.authenticated', 'label', 'Utilizator autentificat', 'ro');

    // Ignore user.role.anonymous and two keys from user.role.authenticated.
    $this->config('config_ignore.settings')->set('ignored_config_entities', [
      'user.role.anonymous',
      'user.role.authenticated:weight',
      'user.role.authenticated:is_admin',
    ])->save();

    // Export the config ignore settings.
    $this->drush('config:export', [], ['yes' => NULL]);
  }

  /**
   * Tests config status.
   */
  public function testConfigStatus() {
    // Change configurations in the active store.
    $this->config('user.settings')->set('anonymous', 'Visitor')->save();
    $this->config('user.role.anonymous')->set('label', 'Visitor')->save();
    $this->config('user.role.authenticated')
      ->set('label', 'Authenticated')
      ->set('weight', 2)
      ->set('is_admin', TRUE)
      ->save();
    // Change translations of user.role.anonymous and user.role.authenticated.
    $this->translateConfig('user.role.anonymous', 'label', 'Vizitator', 'ro');
    $this->translateConfig('user.role.authenticated', 'label', 'Logat', 'ro');

    // Get config status.
    $this->drush('config:status', [], ['format' => 'json']);
    $diff = (array) $this->getOutputFromJSON();

    // Check that only config_ignore.settings and user.settings are shown.
    $this->assertCount(2, $diff);
    $this->assertArrayHasKey('user.settings', $diff);
    $this->assertSame(['name' => 'user.settings', 'state' => 'Different'], $diff['user.settings']);
    $this->assertArrayHasKey('user.role.authenticated', $diff);
    $this->assertSame(['name' => 'user.role.authenticated', 'state' => 'Different'], $diff['user.role.authenticated']);
  }

  /**
   * Tests config export.
   */
  public function testConfigExport() {
    // Change configurations in the active store.
    $this->config('user.settings')->set('anonymous', 'Visitor')->save();
    $this->config('user.role.anonymous')->set('label', 'Visitor')->save();
    $this->config('user.role.authenticated')
      ->set('label', 'Authenticated')
      ->set('weight', 2)
      ->set('is_admin', TRUE)
      ->save();
    // Change translations of user.role.anonymous and user.role.authenticated.
    $this->translateConfig('user.role.anonymous', 'label', 'Vizitator', 'ro');
    $this->translateConfig('user.role.authenticated', 'label', 'Logat', 'ro');

    // Export changes.
    $this->drush('config:export', [], ['yes' => NULL]);

    // Check that user.settings changes were exported.
    $this->assertExportedValue('user.settings', 'anonymous', 'Visitor');
    // Check that the main user.role.anonymous.yml file was not overridden.
    $this->assertExportedValue('user.role.anonymous', 'label', 'Anonymous user');
    // Check that the translated version was not overridden.
    $this->assertExportedValue('user.role.anonymous', 'label', 'Utilizator anonim', 'ro');
    // Check that user.role.authenticated changes were exported.
    $this->assertExportedValue('user.role.authenticated', 'label', 'Authenticated');
    $this->assertExportedValue('user.role.authenticated', 'weight', 1);
    $this->assertExportedValue('user.role.authenticated', 'is_admin', FALSE);
    // Check that the translated version has been exported too.
    $this->assertExportedValue('user.role.authenticated', 'label', 'Logat', 'ro');

    // Delete user.role.authenticated from sync storage in order to test again
    // when the destination is missed.
    $sync_storage = $this->getSyncStorage();
    $sync_storage->delete('user.role.authenticated');

    // Re-export changes.
    $this->drush('config:export', [], ['yes' => NULL]);

    $data = $sync_storage->read('user.role.authenticated');
    // Check that weight & is_admin keys were ignored on the new created config.
    $this->assertArrayNotHasKey('weight', $data);
    $this->assertArrayNotHasKey('is_admin', $data);
  }

  /**
   * Tests config import.
   */
  public function testConfigImport() {
    // Change configurations in the sync store.
    $this->setConfigSyncValue('user.settings', 'anonymous', 'Visitor');
    $this->setConfigSyncValue('user.role.anonymous', 'label', 'Visitor');
    $this->setConfigSyncValue('user.role.authenticated', 'label', 'Authenticated');
    $this->setConfigSyncValue('user.role.authenticated', 'weight', 2);
    $this->setConfigSyncValue('user.role.authenticated', 'is_admin', TRUE);
    // Change translations of user.role.anonymous and user.role.authenticated.
    $this->setConfigSyncValue('user.role.anonymous', 'label', 'Vizitator', 'ro');
    $this->setConfigSyncValue('user.role.authenticated', 'label', 'Logat', 'ro');

    // Check that user.settings was changed in the sync store.
    $this->assertExportedValue('user.settings', 'anonymous', 'Visitor');
    // Check that main user.role.anonymous.yml was changed in the sync store.
    $this->assertExportedValue('user.role.anonymous', 'label', 'Visitor');
    // Check that the translated override was changed in the sync store.
    $this->assertExportedValue('user.role.anonymous', 'label', 'Vizitator', 'ro');
    $this->assertExportedValue('user.role.authenticated', 'label', 'Authenticated');
    $this->assertExportedValue('user.role.authenticated', 'weight', 2);
    $this->assertExportedValue('user.role.authenticated', 'is_admin', TRUE);

    // Import changes.
    $this->drush('config:import', [], ['yes' => NULL]);
    // As the tests are running in the same request we manually clear the static
    // cache of the config objects.
    \Drupal::configFactory()->reset();

    // Check that user.settings has been overridden by import.
    $this->assertSame('Visitor', $this->config('user.settings')->get('anonymous'));
    // Check that user.role.anonymous has been preserved.
    $this->assertSame('Anonymous user', $this->config('user.role.anonymous')->get('label'));
    // Check that user.role.authenticated has been overridden by import.
    $this->assertSame('Authenticated', $this->config('user.role.authenticated')->get('label'));
    $this->assertEquals(1, $this->config('user.role.authenticated')->get('weight'));
    $this->assertFalse($this->config('user.role.authenticated')->get('is_admin'));

    // Check that the user.role.anonymous translation has been also preserved.
    $language_manager = \Drupal::languageManager();
    $original_language = $language_manager->getConfigOverrideLanguage();
    /** @var \Drupal\language\Config\LanguageConfigOverride $translated */
    $translated = $language_manager->getLanguageConfigOverride('ro', 'user.role.anonymous');
    $this->assertSame('Utilizator anonim', $translated->get('label'));
    $translated = $language_manager->getLanguageConfigOverride('ro', 'user.role.authenticated');
    $this->assertSame('Logat', $translated->get('label'));
    $language_manager->setConfigOverrideLanguage($original_language);

    /** @var \Drupal\Core\Config\StorageInterface $active_storage */
    $active_storage = \Drupal::service('config.storage');
    // Remove the config in order to test again when the destination is missed.
    $active_storage->delete('user.role.authenticated');

    // Re-import changes.
    $this->drush('config:import', [], ['yes' => NULL]);
    \Drupal::configFactory()->reset('user.role.authenticated');

    $this->assertSame('Authenticated', $this->config('user.role.authenticated')->get('label'));
    $this->assertEquals(1, $this->config('user.role.authenticated')->get('weight'));
    $this->assertFalse((bool) $this->config('user.role.authenticated')->get('is_admin'));
  }

  /**
   * Asserts that a given value for a given config exists in sync.
   *
   * @param string $config_name
   *   The config name.
   * @param string $key
   *   The key of the config value. It only supports top level keys.
   * @param mixed $value
   *   The value to be checked.
   * @param string|null $langcode
   *   (optional) If passed, the value will be checked in the $langcode language
   *   collection.
   */
  protected function assertExportedValue($config_name, $key, $value, $langcode = NULL) {
    // The file config storage is using file cache for performance reasons. As
    // the tests are running in the same request, the file static cache is not
    // cleared. We do this explicitly before making any assertions regarding
    // exported files.
    FileCache::reset();

    $sync_storage = $this->getSyncStorage($langcode);
    // Check that the destination file has not been deleted.
    $this->assertTrue($sync_storage->exists($config_name));
    // Check that the changed value has been exported.
    $data = $sync_storage->read($config_name);
    $this->assertSame($value, $data[$key]);
  }

  /**
   * Translates $config_name:$key into $langcode language.
   *
   * @param string $config_name
   *   The config name.
   * @param string $key
   *   The config key to be translated. It only supports top level keys.
   * @param string $value
   *   The translated value.
   * @param string $langcode
   *   The langcode.
   */
  protected function translateConfig($config_name, $key, $value, $langcode) {
    $language_manager = \Drupal::languageManager();
    $original_language = $language_manager->getConfigOverrideLanguage();
    /** @var \Drupal\language\Config\LanguageConfigOverride $translated */
    $translated = $language_manager->getLanguageConfigOverride($langcode, $config_name);
    $translated->set($key, $value)->save();
    $language_manager->setConfigOverrideLanguage($original_language);
  }

  /**
   * Sets a config value in the sync store.
   *
   * @param string $config_name
   *   The config name.
   * @param string $key
   *   The key of the config value to be set. It only supports top level keys.
   * @param mixed $value
   *   The value to be set.
   * @param string|null $langcode
   *   (optional) If passed, the value will be set in the $langcode language
   *   collection.
   */
  protected function setConfigSyncValue($config_name, $key, $value, $langcode = NULL) {
    $sync_storage = $this->getSyncStorage($langcode);
    $data = $sync_storage->read($config_name);
    $data[$key] = $value;
    $sync_storage->write($config_name, $data);
  }

  /**
   * Returns the config sync storage.
   *
   * @param string|null $langcode
   *   (optional) The language collection language code or NULL for the default
   *   collection.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The config sync storage.
   */
  protected function getSyncStorage($langcode = NULL) {
    $sync_storage = \Drupal::service('config.storage.sync');
    if ($langcode) {
      $sync_storage = $sync_storage->createCollection("language.{$langcode}");
    }
    return $sync_storage;
  }

}
