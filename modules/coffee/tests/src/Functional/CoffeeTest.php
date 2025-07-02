<?php

namespace Drupal\Tests\coffee\Functional;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\system\Entity\Menu;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests Coffee module functionality.
 *
 * @group coffee
 */
class CoffeeTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['coffee', 'coffee_test', 'menu_link_content'];

  /**
   * The user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * The user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $coffeeUser;

  /**
   * The user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $coffeeAdmin;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->webUser = $this->drupalCreateUser();
    $this->coffeeUser = $this->drupalCreateUser(['access coffee']);
    $this->coffeeAdmin = $this->drupalCreateUser(['administer coffee']);
  }

  /**
   * Tests coffee configuration form.
   */
  public function testCoffeeConfiguration() {
    $this->drupalGet('admin/config/user-interface/coffee');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogin($this->coffeeAdmin);
    $this->drupalGet('admin/config/user-interface/coffee');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->checkboxChecked('edit-coffee-menus-admin');
    $this->assertSession()->fieldValueEquals('edit-max-results', 7);

    $edit = [
      'coffee_menus[tools]' => 'tools',
      'coffee_menus[account]' => 'account',
      'max_results' => 15,
    ];
    $this->drupalGet('admin/config/user-interface/coffee');
    $this->submitForm($edit, t('Save configuration'));
    $this->assertSession()->pageTextContains(t('The configuration options have been saved.'));

    $expected = [
      'admin' => 'admin',
      'tools' => 'tools',
      'account' => 'account',
    ];
    $config = \Drupal::config('coffee.configuration')->get('coffee_menus');
    $this->assertEquals($expected, $config, 'The configuration options have been properly saved');

    $config = \Drupal::config('coffee.configuration')->get('max_results');
    $this->assertEquals(15, $config, 'The configuration options have been properly saved');
  }

  /**
   * Tests coffee configuration cache tags invalidation.
   */
  public function testCoffeeCacheTagsInvalidation() {
    $coffee_cache_tag = 'config:coffee.configuration';
    // Coffee is not loaded for users without the adequate permission,
    // so no cache tags for coffee configuration are added.
    $this->drupalGet('');
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', $coffee_cache_tag);

    // Make sure that the coffee configuration cache tags are present
    // for users with the adequate permission.
    $this->drupalLogin($this->coffeeUser);
    $this->drupalGet('');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', $coffee_cache_tag);
    $settings = $this->getDrupalSettings();
    $this->assertEquals(7, $settings['coffee']['maxResults']);

    // Trigger a config save which should clear the page cache, so we should get
    // the fresh configuration settings.
    $max_results = 10;
    $this->config('coffee.configuration')
      ->set('max_results', $max_results)
      ->save();

    $this->drupalGet('');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', $coffee_cache_tag);
    $settings = $this->getDrupalSettings();
    $this->assertEquals($max_results, $settings['coffee']['maxResults']);
  }

  /**
   * Tests that the coffee assets are loaded properly.
   */
  public function testCoffeeAssets() {
    // Ensure that the coffee assets are not loaded for users without the
    // adequate permission.
    $this->drupalGet('');
    // GitLab CI installs the module under test at a path (e.g.,
    // `modules/custom/coffee-MR_ID`) that we can't necessarily anticipate, so
    // just look for a script tag that mentions the name of the JS file.
    $this->assertSession()->elementNotExists('css', 'script[src*="coffee.js"]');

    // Ensure that the coffee assets are loaded properly for users with the
    // adequate permission.
    $this->drupalLogin($this->coffeeUser);
    $this->drupalGet('');
    $this->assertSession()->elementExists('css', 'script[src*="coffee.js"]');

    // Ensure that the coffee assets are not loaded for users without the
    // adequate permission.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('');
    $this->assertSession()->elementNotExists('css', 'script[src*="coffee.js"]');
  }

  /**
   * Tests that the toolbar integration works properly.
   */
  public function testCoffeeToolbarIntegration() {
    \Drupal::service('module_installer')->install(['toolbar']);
    $tab_xpath = '//nav[@id="toolbar-bar"]//div/a[contains(@class, "toolbar-icon-coffee")]';

    $toolbar_user = $this->drupalCreateUser(['access toolbar']);
    $this->drupalLogin($toolbar_user);
    $this->assertSession()->responseContains('id="toolbar-administration"');
    $this->assertSession()->elementNotExists('xpath', $tab_xpath);

    $coffee_toolbar_user = $this->drupalCreateUser([
      'access toolbar',
      'access coffee',
    ]);
    $this->drupalLogin($coffee_toolbar_user);
    $this->assertSession()->responseContains('id="toolbar-administration"');
    $this->assertSession()->elementExists('xpath', $tab_xpath);
  }

  /**
   * Tests that CSRF tokens are correctly handled.
   */
  public function testCoffeeCsrf() {
    $account = $this->drupalCreateUser([
      'access coffee',
      'access administration pages',
    ]);
    $this->drupalLogin($account);

    // Set up a new menu with one link.
    $menu = Menu::create([
      'id' => 'coffee',
      'label' => 'Coffee',
      'description' => 'Menu for testing Coffee.',
    ]);
    $menu->save();

    $menu_link = MenuLinkContent::create([
      'title' => 'Coffee test',
      'provider' => 'menu_link_content',
      'menu_name' => 'coffee',
      'link' => ['uri' => 'internal:/coffee-test-csrf'],
    ]);
    $menu_link->save();
    $this->config('coffee.configuration')->set('coffee_menus', ['coffee'])->save();

    // Get the link with CSRF token.
    $result = $this->drupalGet('/admin/coffee/get-data');
    $result = json_decode($result);

    // For some reason, drupalGet('path?token=foo') does not work, and
    // we have to explicitly set the token in the query options.
    $token = substr($result[0]->value, strpos($result[0]->value, 'token=') + 6);

    $this->drupalGet('/coffee-test-csrf', ['query' => ['token' => $token]]);
    $this->assertSession()->statusCodeEquals(200);
  }

}
