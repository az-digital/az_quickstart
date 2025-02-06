<?php

namespace Drupal\Tests\az_demo\Functional;

use Drupal\Core\Url;
use Drupal\Tests\az_core\Functional\QuickstartFunctionalTestBase;

/**
 * Verify successful importing of demo content.
 *
 * @group az_demo
 */
class AZDemoContentTest extends QuickstartFunctionalTestBase {

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'az_quickstart';

  /**
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * @var string
   */
  protected $defaultTheme = 'az_barrio';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'az_demo',
  ];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests that imported utility links exist.
   */
  public function testUtilityLinks() {
    // Go to the front page.
    $this->drupalGet(Url::fromRoute('<front>'));
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    // Check for utility links.
    $assert->linkExists('Utility 1');
    $assert->linkExists('Utility 2');
  }

  /**
   * Tests page titles.
   */
  public function testTitle() {
    $this->drupalGet(Url::fromRoute('<front>'));
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);

    // Home page title test.
    $assert->elementContains('css', '#block-az-barrio-page-title h1.title span.field--name-title', 'Kitten');
  }

  /**
   * Tests publication links.
   *
   * @group az_demo_links
   */
  public function testPublicationLinks() {
    $this->drupalGet('/publications');
    $assert = $this->assertSession();
    // Assert the page loads successfully.
    $assert->statusCodeEquals(200);
    // Assert specific text elements are links.
    $assert->linkExists('Ethics in Artificial Intelligence');

    // Ensure specific text elements are NOT links.
    $assert->linkNotExists('Statistical Learning with Applications');
    $assert->linkNotExists('van Gogh, Vincent');
    $assert->linkNotExists('Christian Andersen, Hans');
    $assert->linkNotExists('Ludwig van Beethoven');
  }

}
