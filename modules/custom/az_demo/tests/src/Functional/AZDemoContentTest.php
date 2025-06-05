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
   * @var string[]
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
   * Tests demo content.
   */
  public function testDemoContent() {
    // Go to the front page.
    $this->drupalGet(Url::fromRoute('<front>'));
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);

    // Check for utility links.
    $assert->linkExists('Utility 1');
    $assert->linkExists('Utility 2');

    // Home page title test.
    $assert->elementContains('css', '#block-az-barrio-page-title h1.title span.field--name-title', 'Kitten');

    // Tests publication links.
    $this->drupalGet('/publications');
    $assert = $this->assertSession();
    // Assert the page loads successfully.
    $assert->statusCodeEquals(200);
    // Assert individual links exist with the correct text and href.
    $assert->elementExists('xpath', "//a[@href='/publication/life-leonardo-da-vinci-0' and text()='The Life of Leonardo Da Vinci']");
    $assert->elementExists('xpath', "//a[@href='https://example.com/wildcat-book-review' and text()='The Life of Leonardo Da Vinci']");
    $assert->elementExists('xpath', "//a[@href='/publication/most-fearsome-life-great-gargantua-father-pantagruel' and text()='The Most Fearsome Life of the Great Gargantua, Father of Pantagruel.']");
    $assert->elementExists('xpath', "//a[@href='https://example.com/data-structures-book' and text()='Advanced Data Structures']");
    $assert->elementExists('xpath', "//a[@href='https://example.com/machine-learning-paper' and text()='Modern Approaches to Machine Learning']");
    $assert->elementExists('xpath', "//a[@href='https://example.com/algorithms-book' and text()='Introduction to Algorithms']");
    $assert->elementExists('xpath', "//a[@href='https://example.com/deep-learning-book' and text()='Deep Learning']");
    $assert->elementExists('xpath', "//a[@href='https://example.com/climate-change-journal' and text()='Climate Change: Impacts and Solutions']");
    $siteDirectory = $this->siteDirectory;
    $assert->elementExists('xpath', "//a[@href='/{$siteDirectory}/files/Small-PDF.pdf' and text()='The Trissotetras: Or, a Most Exquisite Table for Resolving All Manner of triangles. ']");
    // If field_az_publication_link has a value, assert that the value is used
    // as the link.
    $assert->elementExists('xpath', "//a[@href='https://example.com/ai-ethics-book' and text()='Ethics in Artificial Intelligence']");
    $assert->elementNotExists('xpath', "//a[@href='/publication/ethics-artificial-intelligence' and span[text()='Ethics in Artificial Intelligence']]");
    // Ensure specific text elements are NOT links.
    // If there is no value in:
    // - field_az_publication_link
    // - field_az_publication_image
    // - field_az_publication_abstract
    // ensure that the title is NOT a link, and that there is
    // no link to the full node.
    $assert->linkNotExists('Statistical Learning with Applications');
    $assert->elementNotExists('xpath', "//a[@href='/publication/statistical-learning-applications' and span[text()='Statistical Learning with Applications']]");
    // If the author IS referenced to a person, ensure that the author
    // IS a link.
    $assert->linkExists('An\'ersen, José, M.A., Ph.D.');
    // If the author is NOT referenced to a person, ensure that the author
    // is NOT a link.
    $assert->linkNotExists('van Gogh, Vincent');
    $assert->linkNotExists('Christian Andersen, Hans');
    $assert->linkNotExists('Ludwig van Beethoven');
  }

}
