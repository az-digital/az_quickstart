<?php

declare(strict_types=1);

namespace Drupal\Tests\flag\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Browser tests for the flag.twig.count service.
 *
 * @see Drupal\flag\TwigExtension\FlagCount
 *
 * @group flag
 */
class FlagCountExtensionTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'views',
    'flag',
    'flag_bookmark',
    'flag_count',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    // Set the linkTypePlugin of the flag to count_link for running the tests.
    $flag_service = \Drupal::service('flag');
    $bookmark_flag = $flag_service->getFlagById('bookmark');
    $bookmark_flag->setlinkTypePlugin('count_link');
    $bookmark_flag->save();
  }

  /**
   * Browser tests for flag count.
   */
  public function testUi() {
    // Generate a unique title so we can find it on the page easily.
    $title = $this->randomMachineName();

    // Add a single article.
    $article = $this->drupalCreateNode(['type' => 'article', 'title' => $title]);

    $auth_user = $this->drupalCreateUser([
      'flag bookmark',
      'unflag bookmark',
    ]);

    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assert_session */
    $assert_session = $this->assertSession();

    $this->drupalLogin($auth_user);

    // Check the link to bookmark exist.
    $this->drupalGet('node/1');

    // Check that the bookmark count is equal to zero.
    $element0 = $assert_session->waitForElementVisible('css', '.flag-bookmark span:contains("[0]")');
    $this->assertNotNull($element0);

    $this->clickLink('Bookmark this');

    // Check that after clicking the link bookmark count is equal to one.
    $element1 = $assert_session->waitForElementVisible('css', '.flag-bookmark span:contains("[1]")');
    $this->assertNotNull($element1);

    // Observe a change in the frontpage link title.
    $bookmark_link = $assert_session->waitForLink('Remove bookmark');
    $this->assertNotNull($bookmark_link, 'Remove bookmark is available on the page.');

    // Check the view is shown correctly.
    $this->drupalGet('bookmarks');
    $this->assertSession()->pageTextContains($article->getTitle());
  }

}
