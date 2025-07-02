<?php

declare(strict_types=1);

namespace Drupal\Tests\flag_bookmark\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Browser tests for flag_bookmark.
 *
 * @group flag_bookmark
 */
class FlagBookmarkUITest extends WebDriverTestBase {

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
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
  }

  /**
   * Browser tests for bookmark link.
   */
  public function testUi() {
    // Add a single article.
    $article = $this->drupalCreateNode(['type' => 'article']);

    $auth_user = $this->drupalCreateUser([
      'flag bookmark',
      'unflag bookmark',
    ]);

    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assert_session */
    $assert_session = $this->assertSession();

    $this->drupalLogin($auth_user);

    // Check the link to bookmark exist.
    $this->drupalGet('node/1');
    $this->clickLink('Bookmark this');

    // Observe a change in the frontpage link title.
    $bookmark_link = $assert_session->waitForLink('Remove bookmark');
    $this->assertNotNull($bookmark_link, 'Remove bookmark is available on the page.');

    // Check the view is shown correctly.
    $this->drupalGet('bookmarks');
    $assert_session->pageTextContains($article->getTitle());
  }

  /**
   * Tests bulk deletion of flaggings form.
   */
  public function testUiBulkDelete() {
    // Create some nodes.
    $articles[] = $this->drupalCreateNode(['type' => 'article']);
    $articles[] = $this->drupalCreateNode(['type' => 'article']);

    // Login as an auth user.
    $admin_user = $this->drupalCreateUser([
      'flag bookmark',
      'unflag bookmark',
      'administer flaggings',
    ]);
    $this->drupalLogin($admin_user);

    $flag_service = \Drupal::service('flag');
    $bookmark_flag = $flag_service->getFlagById('bookmark');

    // Flag the articles.
    $flag_service->flag($bookmark_flag, $articles[0]);
    $flag_service->flag($bookmark_flag, $articles[1]);

    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assert_session */
    $assert_session = $this->assertSession();

    $this->drupalGet('bookmarks');
    $page = $this->getSession()->getPage();

    // Check bulk actions.
    $assert_session->optionExists('action', 'Delete flagging');
    // Confirm both articles appear in the table.
    $assert_session->pageTextContains($articles[0]->label());
    $assert_session->pageTextContains($articles[1]->label());

    // Select action, select all bookmarks and perform bulk delete.
    $page->selectFieldOption('action', 'Delete flagging');
    $page
      ->find('css', 'input[title="Select all rows in this table"]')
      ->check();
    $page->pressButton('Apply to selected items');

    // Assert that the bookmark table has become empty.
    $empty_form = $assert_session->waitForElementVisible('css', "form:contains('No bookmarks available.')");
    $this->assertNotNull($empty_form, 'Flagging form is empty.');
    $assert_session->pageTextNotContains($articles[0]->label());
    $assert_session->pageTextNotContains($articles[1]->label());
  }

}
