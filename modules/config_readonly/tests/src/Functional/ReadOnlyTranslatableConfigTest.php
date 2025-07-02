<?php

namespace Drupal\Tests\config_readonly\Functional;

use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests read-only module config functionality.
 *
 * @group ConfigReadOnly
 */
class ReadOnlyTranslatableConfigTest extends ReadOnlyConfigTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['language', 'locale_test_translate', 'config', 'config_readonly', 'config_translation'];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($this->adminUser);

    // Add a language. The Afrikaans translation file of locale_test_translate
    // (test.af.po) has been prepared with a configuration translation.
    ConfigurableLanguage::createFromLangcode('af')->save();

    // Enable locale module.
    $this->container->get('module_installer')->install(['locale']);
    $this->resetAll();

    // Enable import of translations. By default this is disabled for automated
    // tests.
    $this->config('locale.settings')
      ->set('translation.import_enabled', TRUE)
      ->set('translation.use_source', LOCALE_TRANSLATION_USE_SOURCE_LOCAL)
      ->save();
  }

  /**
   * Tests translatable config forms.
   *
   * @see https://www.drupal.org/project/config_readonly/issues/3452833
   */
  public function testTranslatableConfig() {
    // Verify if we can successfully access config translation route.
    $config_translation_url = '/admin/config/system/site-information/translate/af/add';
    $this->drupalGet($config_translation_url);
    $this->assertSession()->statusCodeEquals(200);
    // The translation config form is not read-only.
    $this->assertSession()->pageTextNotContains($this->message);

    // Switch forms to read-only.
    $this->turnOnReadOnlySetting();
    $this->drupalGet($config_translation_url);
    // The file system config form is read-only.
    $this->assertSession()->pageTextContains($this->message);
  }

}
