<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Ensures that meta tag values are translated correctly on nodes.
 *
 * @group metatag
 */
class MetatagNodeTranslationTest extends BrowserTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'content_translation',
    'field_ui',
    'metatag',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The default language code to use in this test.
   *
   * @var array
   */
  protected $defaultLangcode = 'fr';

  /**
   * Languages to enable.
   *
   * @var array
   */
  protected $additionalLangcodes = ['es'];

  /**
   * Administrator user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Setup basic environment.
   */
  protected function setUp(): void {
    parent::setUp();

    $admin_permissions = [
      'administer content types',
      'administer content translation',
      'administer languages',
      'administer nodes',
      'administer node fields',
      'bypass node access',
      'create content translations',
      'delete content translations',
      'translate any entity',
      'update content translations',
    ];

    // Create and login user.
    $this->adminUser = $this->drupalCreateUser($admin_permissions);

    // Add languages.
    foreach ($this->additionalLangcodes as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }

    // Set up a content type.
    $name = $this->randomMachineName() . ' ' . $this->randomMachineName();
    $this->drupalLogin($this->adminUser);
    $this->drupalCreateContentType(['type' => 'metatag_node', 'name' => $name]);

    // Add a metatag field to the content type.
    $this->drupalGet('admin/structure/types');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/structure/types/manage/metatag_node');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'language_configuration[language_alterable]' => TRUE,
      'language_configuration[content_translation]' => TRUE,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);

    $this->fieldUIAddNewField(
      'admin/structure/types/manage/metatag_node',
      'meta_tags',
      'Metatag',
      'metatag',
      [],
      ['translatable' => TRUE]
    );
    $this->drupalGet('admin/structure/types/manage/metatag_node/fields/node.metatag_node.field_meta_tags');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Confirm the language translation system isn't accidentally broken.
   */
  public function testContentTranslationForm() {
    $this->drupalGet('/admin/config/regional/content-language');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Content language');
    $this->submitForm([], 'Save configuration');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Settings successfully updated.');
  }

  /**
   * Tests the metatag value translations.
   */
  public function testMetatagValueTranslation() {
    $save_label_i18n = 'Save (this translation)';

    // Set up a node without explicit metatag description. This causes the
    // global default to be used, which contains a token (node:summary). The
    // token value should be correctly translated.
    // Load the node form.
    $this->drupalGet('node/add/metatag_node');
    $this->assertSession()->statusCodeEquals(200);

    // Check the default values are correct.
    $this->assertSession()->fieldValueEquals('field_meta_tags[0][basic][title]', '[node:title] | [site:name]');
    $this->assertSession()->fieldValueEquals('field_meta_tags[0][basic][description]', '[node:summary]');

    // Create a node.
    $edit = [
      'title[0][value]' => 'Node Français',
      'body[0][value]' => 'French summary.',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);

    $xpath = $this->xpath("//meta[@name='description']");
    $this->assertCount(1, $xpath, 'Exactly one description meta tag found.');
    $value = $xpath[0]->getAttribute('content');
    $this->assertEquals($value, 'French summary.');

    $this->drupalGet('node/1/translations/add/en/es');
    $this->assertSession()->statusCodeEquals(200);
    // Check the default values are there.
    $this->assertSession()->fieldValueEquals('field_meta_tags[0][basic][title]', '[node:title] | [site:name]');
    $this->assertSession()->fieldValueEquals('field_meta_tags[0][basic][description]', '[node:summary]');

    $edit = [
      'title[0][value]' => 'Node Español',
      'body[0][value]' => 'Spanish summary.',
    ];
    $this->submitForm($edit, $save_label_i18n);
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('es/node/1');
    $this->assertSession()->statusCodeEquals(200);
    $xpath = $this->xpath("//meta[@name='description']");
    $this->assertCount(1, $xpath, 'Exactly one description meta tag found.');
    $value = $xpath[0]->getAttribute('content');
    $this->assertEquals($value, 'Spanish summary.');
    $this->assertNotEquals($value, 'French summary.');

    $this->drupalGet('node/1/edit');
    $this->assertSession()->statusCodeEquals(200);
    // Check the default values are there.
    $this->assertSession()->fieldValueEquals('field_meta_tags[0][basic][title]', '[node:title] | [site:name]');
    $this->assertSession()->fieldValueEquals('field_meta_tags[0][basic][description]', '[node:summary]');

    // Set explicit values on the description metatag instead using the
    // defaults.
    $this->drupalGet('node/1/edit');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'field_meta_tags[0][basic][description]' => 'Overridden French description.',
    ];
    $this->submitForm($edit, $save_label_i18n);
    $this->assertSession()->statusCodeEquals(200);

    $xpath = $this->xpath("//meta[@name='description']");
    $this->assertCount(1, $xpath, 'Exactly one description meta tag found.');
    $value = $xpath[0]->getAttribute('content');
    $this->assertEquals($value, 'Overridden French description.');
    $this->assertNotEquals($value, 'Spanish summary.');
    $this->assertNotEquals($value, 'French summary.');

    $this->drupalGet('es/node/1/edit');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'field_meta_tags[0][basic][description]' => 'Overridden Spanish description.',
    ];
    $this->submitForm($edit, $save_label_i18n);
    $this->assertSession()->statusCodeEquals(200);

    $xpath = $this->xpath("//meta[@name='description']");
    $this->assertCount(1, $xpath, 'Exactly one description meta tag found.');
    $value = $xpath[0]->getAttribute('content');
    $this->assertEquals($value, 'Overridden Spanish description.');
    $this->assertNotEquals($value, 'Spanish summary.');
    $this->assertNotEquals($value, 'French summary.');
  }

}
