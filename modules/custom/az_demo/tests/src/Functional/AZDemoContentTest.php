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
   */
  public function testPublicationLinks() {
    $this->drupalGet('/publications');
    $assert = $this->assertSession();
    // Assert the page loads successfully.
    $assert->statusCodeEquals(200);
    // Assert individual links exist with the correct text and href.
    $assert->elementExists('xpath', "//a[@href='/publication/life-leonardo-da-vinci-0' and text()='The Life of Leonardo Da Vinci']");
    $assert->elementExists('xpath', "//a[@href='https://example.com/wildcat-book-review' and text()='The Life of Leonardo Da Vinci']");
    $assert->elementExists('xpath', "//a[@href='/publication/most-fearsome-life-great-gargantua-father-pantagruel' and text()='The Most Fearsome Life of the Great Gargantua, Father of Pantagruel.']");
    $assert->elementExists('xpath', "//a[@href='/sites/default/files/Small-PDF.pdf' and text()='The Trissotetras: Or, a Most Exquisite Table for Resolving All Manner of triangles.']");
    $assert->elementExists('xpath', "//a[@href='/publication/exploration-quantum-mechanics' and text()='An Exploration of Quantum Mechanics']");
    $assert->elementExists('xpath', "//a[@href='https://example.com/data-structures-book' and text()='Advanced Data Structures']");
    $assert->elementExists('xpath', "//a[@href='https://example.com/machine-learning-paper' and text()='Modern Approaches to Machine Learning']");
    $assert->elementExists('xpath', "//a[@href='https://example.com/algorithms-book' and text()='Introduction to Algorithms']");
    $assert->elementExists('xpath', "//a[@href='https://example.com/deep-learning-book' and text()='Deep Learning']");
    $assert->elementExists('xpath', "//a[@href='https://example.com/statistical-learning-journal' and text()='Statistical Learning with Applications']");
    $assert->elementExists('xpath', "//a[@href='/publication/ethics-artificial-intelligence' and text()='Ethics in Artificial Intelligence']");
    $assert->elementExists('xpath', "//a[@href='/publication/climate-change-impacts-and-solutions' and text()='Climate Change: Impacts and Solutions']");

    // Ensure specific text elements are NOT links.
    $assert->elementNotExists('xpath', "//a[text()='van Gogh, Vincent.']");
    $assert->elementNotExists('xpath', "//a[text()='An Exploration of Quantum Mechanics']");
  }

}
