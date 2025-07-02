<?php

namespace Drupal\Tests\metatag_custom_tags\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Custom tags.
 *
 * @group metatag_custom_tags
 */
class MetatagCustomTagsTest extends BrowserTestBase {

  use MetatagCustomTagHelperTrait;

  /**
   * Profile to use.
   *
   * @var string
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'node',
    'user',
    'metatag',
    'metatag_custom_tags',
    'metatag_test_custom_route',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with admin permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->adminUser = $this
      ->drupalCreateUser([
        'administer site configuration',
        'administer meta tags',
        'administer custom meta tags',
        'access content',
      ]);
  }

  /**
   * Tests the metatag custom tag http-equiv.
   */
  public function testMetatagCustomTagHttpEquiv() {
    $this->drupalLogin($this->adminUser);
    // Perform metatag custom tag add operation from the listing page.
    $this->createCustomMetaTag('meta', 'http-equiv', 'content');
    // Rebuild cache.
    $this->rebuildAll();
    // Save the value into the metatag custom tag.
    $this->drupalGet('/admin/config/search/metatag/global');
    $this->submitForm(['metatag_custom_tag:foo' => 'foo value'], 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Saved the Global Metatag defaults.');
    // Load the metatag custom route to verify metatag custom tag exists.
    $this->drupalGet('/metatag_test_custom_route');
    $this->assertSession()->elementExists('xpath', '//meta[@http-equiv="foo" and @content="foo value"]');
  }

  /**
   * Tests the metatag custom tag name.
   */
  public function testMetatagCustomTagName() {
    $this->drupalLogin($this->adminUser);
    // Perform metatag custom tag add operation from the listing page.
    $this->createCustomMetaTag('meta', 'name', 'content');
    // Rebuild cache.
    $this->rebuildAll();
    // Save the value into the metatag custom tag.
    $this->drupalGet('/admin/config/search/metatag/global');
    $this->submitForm(['metatag_custom_tag:foo' => 'foo value'], 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Saved the Global Metatag defaults.');
    // Load the metatag custom route to verify metatag custom tag exists.
    $this->drupalGet('/metatag_test_custom_route');
    $this->assertSession()->elementExists('xpath', '//meta[@name="foo" and @content="foo value"]');
  }

  /**
   * Tests the metatag custom tag property.
   */
  public function testMetatagCustomTagProperty() {
    $this->drupalLogin($this->adminUser);
    // Perform metatag custom tag add operation from the listing page.
    $this->createCustomMetaTag('meta', 'property', 'content');
    // Rebuild cache.
    $this->rebuildAll();
    // Save the value into the metatag custom tag.
    $this->drupalGet('/admin/config/search/metatag/global');
    $this->submitForm(['metatag_custom_tag:foo' => 'foo value'], 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Saved the Global Metatag defaults.');
    // Load the metatag custom route to verify metatag custom tag exists.
    $this->drupalGet('/metatag_test_custom_route');
    $this->assertSession()->elementExists('xpath', '//meta[@property="foo" and @content="foo value"]');
  }

  /**
   * Tests the metatag custom tag ItemProp.
   */
  public function testMetatagCustomTagItemProp() {
    $this->drupalLogin($this->adminUser);
    // Perform metatag custom tag add operation from the listing page.
    $this->createCustomMetaTag('meta', 'itemprop', 'content');
    // Rebuild cache.
    $this->rebuildAll();
    // Save the value into the metatag custom tag.
    $this->drupalGet('/admin/config/search/metatag/global');
    $this->submitForm(['metatag_custom_tag:foo' => 'foo value'], 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Saved the Global Metatag defaults.');
    // Load the metatag custom route to verify metatag custom tag exists.
    $this->drupalGet('/metatag_test_custom_route');
    $this->assertSession()->elementExists('xpath', '//meta[@itemprop="foo" and @content="foo value"]');
  }

  /**
   * Tests the metatag custom tag property.
   */
  public function testMetatagCustomTagLinkRel() {
    $this->drupalLogin($this->adminUser);
    // Perform metatag custom tag add operation from the listing page.
    $this->createCustomMetaTag('link', 'rel', 'href');
    // Rebuild cache.
    $this->rebuildAll();
    // Save the value into the metatag custom tag.
    $this->drupalGet('/admin/config/search/metatag/global');
    $this->submitForm(['metatag_custom_tag:foo' => 'foo value'], 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Saved the Global Metatag defaults.');
    // Load the metatag custom route to verify metatag custom tag exists.
    $this->drupalGet('/metatag_test_custom_route');
    $this->assertSession()->elementExists('xpath', '//link[@rel="foo" and @href="foo value"]');
  }

}
