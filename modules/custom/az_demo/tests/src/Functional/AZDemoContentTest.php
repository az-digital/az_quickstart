<?php

namespace Drupal\Tests\az_demo\Functional;

use Drupal\Core\Url;
use Drupal\Core\Config\FileStorage;
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
   * @var string[]
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
    $assert->elementExists('xpath', "//a[@href='/{$siteDirectory}/files/Small-PDF.pdf' and text()='The Trissotetras: Or, a Most Exquisite Table for Resolving All Manner of Triangles. ']");
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

  /**
   * Ensures sidebar blocks only appear when they have content.
   */
  public function testSidebarLayoutClass() {
    $this->config('block.block.az_barrio_sidebar_menu')->set('status', FALSE)->save();

    $fixture_path = __DIR__ . '/../../fixtures/config';
    $storage = new FileStorage($fixture_path);
    $config_name = 'block.block.az_demo_test_sidebar_menu';
    $config_data = $storage->read($config_name);
    $this->assertNotEmpty($config_data, 'Sidebar menu fixture config found.');
    $this->config($config_name)->setData($config_data)->save();
    $this->container->get('entity_type.manager')->getStorage('block')->resetCache();

    // Front page should not display the sidebar menu.
    $this->drupalGet(Url::fromRoute('<front>'));
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $body = $assert->elementExists('css', 'body');
    $classes = $body->getAttribute('class');
    $this->assertStringNotContainsString('layout-one-sidebar', $classes, 'Since the block is empty, the `layout-one-sidebar` class should NOT be present on the front page.');
    $this->assertStringNotContainsString('layout-sidebar-first', $classes, 'Since the block is empty, the `layout-sidebar-first` class should NOT be present on the front page.');
    $this->assertStringContainsString('layout-no-sidebars', $classes, 'Since the block is empty, the `layout-no-sidebars` class SHOULD be present on the front page.');
    $assert->elementNotExists('css', '#block-az-demo-test-sidebar-menu');

    // Demo page set in visibility conditions to not display the block.
    $this->drupalGet('/pages/combo-page-no-sidebar');
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $body = $assert->elementExists('css', 'body');
    $classes = $body->getAttribute('class');
    $this->assertStringNotContainsString('layout-one-sidebar', $classes, 'The block is configured to not appear on this page, so the `layout-one-sidebar` class should NOT be present on the no-sidebar page.');
    $this->assertStringNotContainsString('layout-sidebar-first', $classes, 'The block is configured to not appear on this page, so the `layout-sidebar-first` class should NOT be present on the no-sidebar page.');
    $this->assertStringContainsString('layout-no-sidebars', $classes, 'The block is configured to not appear on this page, so the `layout-no-sidebars` class SHOULD be present on the no-sidebar page.');
    $assert->elementNotExists('css', '#block-az-demo-test-sidebar-menu', 'The block is configured to not appear on this page, so the sidebar menu block should NOT be present on the no-sidebar page.');

    // Pages with content in the menu should render the sidebar.
    $this->drupalGet('/pages/text');
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $body = $assert->elementExists('css', 'body');
    $classes = $body->getAttribute('class');
    $this->assertStringContainsString('layout-one-sidebar', $classes, 'Since the block has content, the `layout-one-sidebar` class should be present on the page.');
    $this->assertStringContainsString('layout-sidebar-first', $classes, 'Since the block has content, the `layout-sidebar-first` class should be present on the page.');
    $this->assertStringNotContainsString('layout-no-sidebars', $classes, 'Since the block has content, the `layout-no-sidebars` class should NOT be present on the page.');
    $assert->elementExists('css', '#block-az-demo-test-sidebar-menu');

    // Finder pages hide the menu but keep other sidebar blocks visible.
    $this->drupalGet('/finders/news');
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $body = $assert->elementExists('css', 'body');
    $classes = $body->getAttribute('class');
    $this->assertStringContainsString('layout-one-sidebar', $classes, 'The `layout-one-sidebar` class should be present on the finder page even though the block is set to not appear on finder pages.');
    $this->assertStringContainsString('layout-sidebar-first', $classes, 'The `layout-sidebar-first` class should be present on the finder page even though the block is set to not appear on finder pages.');
    $this->assertStringNotContainsString('layout-no-sidebars', $classes, 'The `layout-no-sidebars` class should NOT be present on the finder page, since there are other blocks in the sidebar.');
    $assert->elementNotExists('css', '#block-az-demo-test-sidebar-menu');
  }

}
