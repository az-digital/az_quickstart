<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\metatag\Entity\MetatagDefaults;
use Drupal\Tests\BrowserTestBase;

/**
 * Ensures that the Metatag config translations work correctly.
 *
 * @group metatag
 */
class MetatagConfigTranslationTest extends BrowserTestBase {

  /**
   * Profile to use.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * Admin user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'metatag',
    'language',
    'config_translation',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    // From Metatag.
    'administer meta tags',

    // From system module, in order to access the /admin pages.
    'access administration pages',

    // From language module.
    'administer languages',

    // From config_translations module.
    'translate configuration',
  ];

  /**
   * Sets the test up.
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($this->adminUser);

    // Enable the French language.
    ConfigurableLanguage::createFromLangcode('fr')->save();
  }

  /**
   * Confirm the config defaults show on the translations page.
   */
  public function testConfigTranslationsExist() {
    // Ensure the config shows on the admin form.
    $this->drupalGet('admin/config/regional/config-translation');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $session->pageTextContains('Metatag defaults');

    // Load the main metatag_defaults config translation page.
    $this->drupalGet('admin/config/regional/config-translation/metatag_defaults');
    $session->statusCodeEquals(200);
    // @todo Update this to confirm the H1 is loaded.
    $session->responseContains('Metatag defaults');

    // Load all of the Metatag defaults.
    $defaults = \Drupal::configFactory()->listAll('metatag.metatag_defaults');

    /** @var \Drupal\Core\Config\ConfigManagerInterface $config_manager */
    $config_manager = \Drupal::service('config.manager');

    // Confirm each of the configs is available on the translation form.
    foreach ($defaults as $config_name) {
      if ($config_entity = $config_manager->loadConfigEntityByName($config_name)) {
        $session->pageTextContains($config_entity->label());
      }
    }

    // Confirm that each config translation page can be loaded.
    foreach ($defaults as $config_name) {
      $config_entity = $config_manager->loadConfigEntityByName($config_name);
      $this->assertNotNull($config_entity);
      $this->drupalGet('admin/config/search/metatag/' . $config_entity->id() . '/translate');
      $session->statusCodeEquals(200);
    }
  }

  /**
   * Confirm the global configs are translatable page.
   */
  public function testConfigTranslations() {
    // Add something to the Global config.
    $this->drupalGet('admin/config/search/metatag/global');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $edit = [
      'title' => 'Test title',
      'description' => 'Test description',
    ];
    $this->submitForm($edit, 'Save');
    $session->statusCodeEquals(200);
    $session->pageTextContains('Saved the Global Metatag defaults.');

    // Confirm the config has languages available to translate into.
    $this->drupalGet('admin/config/search/metatag/global/translate');
    $session->statusCodeEquals(200);

    // Load the translation form.
    $this->drupalGet('admin/config/search/metatag/global/translate/fr/add');
    $session->statusCodeEquals(200);

    // Confirm the meta tag fields are shown on the form. Confirm the fields and
    // values separately to make it easier to pinpoint where the problem is if
    // one should fail.
    $session->fieldExists('translation[config_names][metatag.metatag_defaults.global][tags][title]');
    $session->fieldValueEquals('translation[config_names][metatag.metatag_defaults.global][tags][title]', $edit['title']);
    $session->fieldExists('translation[config_names][metatag.metatag_defaults.global][tags][description]');
    $session->fieldValueEquals('translation[config_names][metatag.metatag_defaults.global][tags][description]', $edit['description']);

    // Confirm the form can be saved correctly.
    $edit = [
      'translation[config_names][metatag.metatag_defaults.global][tags][title]' => 'Le title',
      'translation[config_names][metatag.metatag_defaults.global][tags][description]' => 'Le description',
    ];
    $this->submitForm($edit, 'Save translation');
    $session->statusCodeEquals(200);
    $session->pageTextContains('Successfully saved French translation');

    // Delete the node metatag defaults to simplify the test.
    MetatagDefaults::load('node')->delete();

    // Create a node in french, request default tags for it. Ensure that the
    // config translation language is afterwards still/again set to EN and
    // tags are returned in FR.
    $this->drupalCreateContentType(['type' => 'page']);
    $node = $this->drupalCreateNode([
      'title' => 'Metatag Test FR',
      'langcode' => 'fr',
    ]);

    $language_manager = \Drupal::languageManager();
    $this->assertEquals('en', $language_manager->getConfigOverrideLanguage()->getId());
    $fr_default_tags = metatag_get_default_tags($node);
    $this->assertEquals('Le title', $fr_default_tags['title']);
    $this->assertEquals('Le description', $fr_default_tags['description']);
    $this->assertEquals('en', $language_manager->getConfigOverrideLanguage()->getId());

    // Delete the default tags as well to test the early return.
    MetatagDefaults::load('global')->delete();
    $fr_default_tags = metatag_get_default_tags($node);
    $this->assertNull($fr_default_tags);
    $this->assertEquals('en', $language_manager->getConfigOverrideLanguage()->getId());
  }

}
