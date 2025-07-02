<?php

namespace Drupal\Tests\metatag_custom_tags\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Metatag: Custom tags administration.
 *
 * @group metatag_custom_tags
 */
class MetatagCustomTagAdministrationTest extends BrowserTestBase {

  use MetatagCustomTagHelperTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'node',
    'user',
    'metatag',
    'metatag_custom_tags',
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
    $this->drupalPlaceBlock('local_actions_block');

    $this->adminUser = $this
      ->drupalCreateUser([
        'administer custom meta tags',
      ]);
  }

  /**
   * Tests the Custom tag administration end to end.
   */
  public function testMetatagCustomTagAdministration() {
    $this->drupalLogin($this->adminUser);
    // Remove default custom meta tags.
    $this->removeDefaultCustomTags();
    // Check metatag custom tag listing empty text.
    $this->metatagCustomTagListingEmptyText();
    // Access metatag custom tag listing page operations for name type.
    $this->metatagCustomTagListingPageOperations('meta', 'name', 'content');
    // Access metatag custom tag listing page operations for property type.
    $this->metatagCustomTagListingPageOperations('meta', 'name', 'content');
    // Access metatag custom tag listing page operations for http-equiv type.
    $this->metatagCustomTagListingPageOperations('meta', 'http-equiv', 'content');
  }

  /**
   * Test metatag custom tag listing page operations.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function metatagCustomTagListingPageOperations($htmlElement, $htmlNameAttribute, $htmlValueAttribute) {
    // Create custom meta tag.
    $this->createCustomMetaTag($htmlElement, $htmlNameAttribute, $htmlValueAttribute);
    // Update custom meta tag.
    $this->updateCustomMetaTag();
    // Delete custom meta tag.
    $this->deleteCustomMetaTag();
  }

}
