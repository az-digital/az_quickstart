<?php

namespace Drupal\Tests\pathauto\FunctionalJavascript;

use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\pathauto\PathautoState;
use Drupal\Tests\pathauto\Functional\PathautoTestHelperTrait;

/**
 * Test pathauto functionality with localization and translation.
 *
 * @group pathauto
 */
class PathautoLocaleTest extends WebDriverTestBase {

  use PathautoTestHelperTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'pathauto', 'locale', 'content_translation'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create Article node type.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
  }

  /**
   * Test that when an English node is updated, its old English alias is
   * updated and its newer French alias is left intact.
   */
  public function testLanguageAliases() {

    $this->createPattern('node', '/content/[node:title]');

    // Add predefined French language.
    ConfigurableLanguage::createFromLangcode('fr')->save();

    $node = [
      'title' => 'English node',
      'langcode' => 'en',
      'path' => [[
        'alias' => '/english-node',
        'pathauto' => FALSE,
      ]],
    ];
    $node = $this->drupalCreateNode($node);
    $english_alias = $this->loadPathAliasByConditions(['alias' => '/english-node', 'langcode' => 'en']);
    $this->assertNotEmpty($english_alias, 'Alias created with proper language.');

    // Also save a French alias that should not be left alone, even though
    // it is the newer alias.
    $this->saveEntityAlias($node, '/french-node', 'fr');

    // Add an alias with the soon-to-be generated alias, causing the upcoming
    // alias update to generate a unique alias with the '-0' suffix.
    $this->createPathAlias('/node/invalid', '/content/english-node', Language::LANGCODE_NOT_SPECIFIED);

    // Update the node, triggering a change in the English alias.
    $node->path->pathauto = PathautoState::CREATE;
    $node->save();

    // Check that the new English alias replaced the old one.
    $this->assertEntityAlias($node, '/content/english-node-0', 'en');
    $this->assertEntityAlias($node, '/french-node', 'fr');
    $this->assertAliasExists(['id' => $english_alias->id(), 'alias' => '/content/english-node-0']);

    // Create a new node with the same title as before but without
    // specifying a language.
    $node = $this->drupalCreateNode(['title' => 'English node', 'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED]);

    // Check that the new node had a unique alias generated with the '-0'
    // suffix.
    $this->assertEntityAlias($node, '/content/english-node-0', LanguageInterface::LANGCODE_NOT_SPECIFIED);
  }

  /**
   * Test that patterns work on multilingual content.
   */
  public function testLanguagePatterns() {

    // Allow other modules to add additional permissions for the admin user.
    $permissions = [
      'administer pathauto',
      'administer url aliases',
      'bulk delete aliases',
      'bulk update aliases',
      'create url aliases',
      'bypass node access',
      'access content overview',
      'administer languages',
      'translate any entity',
      'administer content translation',
      'create content translations',
    ];
    $admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($admin_user);

    // Add French language.
    $edit = [
      'predefined_langcode' => 'fr',
    ];
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add language');

    $this->enableArticleTranslation();

    // Create a pattern for English articles.
    $this->drupalGet('admin/config/search/path/patterns/add');

    $session = $this->getSession();
    $page = $session->getPage();
    $page->fillField('type', 'canonical_entities:node');
    $this->assertSession()->assertWaitOnAjaxRequest();
    sleep(1);

    $page->fillField('label', 'English articles');
    $this->assertSession()->waitForElementVisible('css', '#edit-label-machine-name-suffix .machine-name-value');
    $edit = [
      'bundles[article]' => TRUE,
      'languages[en]' => TRUE,
      'pattern' => '/the-articles/[node:title]',
    ];
    $this->submitForm($edit, 'Save');

    $this->assertSession()->pageTextContains('Pattern English articles saved.');

    // Create a pattern for French articles.
    $this->drupalGet('admin/config/search/path/patterns/add');

    $page->fillField('type', 'canonical_entities:node');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $page->fillField('label', 'French articles');
    $this->assertSession()->waitForElementVisible('css', '#edit-label-machine-name-suffix .machine-name-value');

    $edit = [
      'bundles[article]' => TRUE,
      'languages[fr]' => TRUE,
      'pattern' => '/les-articles/[node:title]',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Pattern French articles saved.');

    // Create a node and its translation. Assert aliases.
    $edit = [
      'title[0][value]' => 'English node',
      'langcode[0][value]' => 'en',
    ];
    $this->drupalGet('node/add/article');
    $this->submitForm($edit, 'Save');
    $node = $this->drupalGetNodeByTitle('English node');
    $this->assertAlias('/node/' . $node->id(), '/the-articles/english-node', 'en');

    $this->drupalGet('node/' . $node->id() . '/translations');
    $this->clickLink('Add');
    $edit = [
      'title[0][value]' => 'French node',
    ];
    $this->submitForm($edit, 'Save (this translation)');
    $this->rebuildContainer();
    $this->assertAlias('/node/' . $node->id(), '/les-articles/french-node', 'fr');

    // Bulk delete and Bulk generate patterns. Assert aliases.
    $this->deleteAllAliases();
    // Bulk create aliases.
    $edit = [
      'update[canonical_entities:node]' => TRUE,
    ];
    $this->drupalGet('admin/config/search/path/update_bulk');
    $this->submitForm($edit, 'Update');
    $this->assertSession()->waitForText('Generated 2 URL aliases.');
    $this->assertAlias('/node/' . $node->id(), '/the-articles/english-node', 'en');
    $this->assertAlias('/node/' . $node->id(), '/les-articles/french-node', 'fr');
  }

  /**
   * Tests the alias created for a node with language Not Applicable.
   */
  public function testLanguageNotApplicable() {
    $this->drupalLogin($this->rootUser);
    $this->enableArticleTranslation();

    // Create a pattern for nodes.
    $pattern = $this->createPattern('node', '/content/[node:title]', -1);
    $pattern->save();

    // Create a node with language Not Applicable.
    $node = $this->createNode([
      'type' => 'article',
      'title' => 'Test node',
      'langcode' => LanguageInterface::LANGCODE_NOT_APPLICABLE,
    ]);

    // Check that the generated alias has language Not Specified.
    $alias = \Drupal::service('pathauto.alias_storage_helper')->loadBySource('/node/' . $node->id());
    $this->assertEquals(LanguageInterface::LANGCODE_NOT_SPECIFIED, $alias['langcode'], 'PathautoGenerator::createEntityAlias() adjusts the alias langcode from Not Applicable to Not Specified.');

    // Check that the alias works.
    $this->drupalGet('content/test-node');
    $this->assertSession()->pageTextContains('Test node');
  }

  /**
   * Enables content translation on articles.
   */
  protected function enableArticleTranslation() {
    // Enable content translation on articles.
    $this->drupalGet('admin/config/regional/content-language');

    // Enable translation for node.
    $this->assertSession()->fieldExists('entity_types[node]')->check();
    // Open details for Content settings in Drupal 10.2.
    $nodeSettings = $this->getSession()->getPage()->find('css', '#edit-settings-node summary');
    if ($nodeSettings) {
      $nodeSettings->click();
    }
    $this->assertSession()->fieldExists('settings[node][article][translatable]')->check();
    $this->assertSession()->fieldExists('settings[node][article][settings][language][language_alterable]')->check();

    $this->getSession()->getPage()->pressButton('Save configuration');
  }

}
