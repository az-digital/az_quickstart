<?php

declare(strict_types=1);

namespace Drupal\Tests\redirect_404\Functional;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;

/**
 * UI tests for redirect_404 module.
 *
 * @group redirect_404
 */
class Fix404RedirectUITest extends Redirect404TestBase {

  /**
   * Tests the fix 404 pages workflow.
   */
  public function testFix404Pages() {
    // Visit a non existing page to have the 404 redirect_error entry.
    $this->drupalGet('non-existing0');

    // Go to the "fix 404" page and check the listing.
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertSession()->pageTextContains('non-existing0');
    $this->clickLink('Add redirect');

    // Check if we generate correct Add redirect url and if the form is
    // pre-filled.
    $destination = Url::fromRoute('redirect_404.fix_404')->getInternalPath();
    $expected_query = [
      'destination' => $destination,
      'language' => 'en',
      'source' => 'non-existing0',
    ];
    $parsed_url = UrlHelper::parse($this->getUrl());
    $this->assertEquals($parsed_url['path'], Url::fromRoute('redirect.add')->setAbsolute()->toString());
    $this->assertEquals($parsed_url['query'], $expected_query);
    $this->assertSession()->fieldValueEquals('redirect_source[0][path]', 'non-existing0');
    // Save the redirect.
    $edit = ['redirect_redirect[0][uri]' => '/node'];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->addressEquals('admin/config/search/redirect/404');
    $this->assertSession()->pageTextContains('There are no 404 errors to fix.');
    // Check if the redirect works as expected.
    $this->drupalGet('non-existing0');
    $this->assertSession()->addressEquals('node');

    // Test removing a redirect assignment, visit again the non existing page.
    $this->drupalGet('admin/config/search/redirect');
    $this->assertSession()->pageTextContains('non-existing0');
    $this->clickLink('Delete', 0);
    $this->submitForm([], 'Delete');
    $this->assertSession()->addressEquals('admin/config/search/redirect');
    $this->assertSession()->pageTextContains('There is no redirect yet.');
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertSession()->pageTextContains('There are no 404 errors to fix.');
    // Should be listed again in the 404 overview.
    $this->drupalGet('non-existing0');
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertSession()->pageTextContains('non-existing0');

    // Visit multiple non existing pages to test the Redirect 404 View.
    $this->drupalGet('non-existing0?test=1');
    $this->drupalGet('non-existing0?test=2');
    $this->drupalGet('non-existing1');
    $this->drupalGet('non-existing2');
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertSession()->pageTextContains('non-existing0?test=1');
    $this->assertSession()->pageTextContains('non-existing0?test=2');
    $this->assertSession()->pageTextContains('non-existing0');
    $this->assertSession()->pageTextContains('non-existing1');
    $this->assertSession()->pageTextContains('non-existing2');

    // Test the Path view filter.
    $this->drupalGet('admin/config/search/redirect/404', ['query' => ['path' => 'test=']]);
    $this->assertSession()->pageTextContains('non-existing0?test=1');
    $this->assertSession()->pageTextContains('non-existing0?test=2');
    $this->assertSession()->pageTextNotContains('non-existing1');
    $this->assertSession()->pageTextNotContains('non-existing2');
    $this->drupalGet('admin/config/search/redirect/404', ['query' => ['path' => 'existing1']]);
    $this->assertSession()->pageTextNotContains('non-existing0?test=1');
    $this->assertSession()->pageTextNotContains('non-existing0?test=2');
    $this->assertSession()->pageTextNotContains('non-existing0');
    $this->assertSession()->pageTextContains('non-existing1');
    $this->assertSession()->pageTextNotContains('non-existing2');
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertSession()->pageTextContains('non-existing0?test=1');
    $this->assertSession()->pageTextContains('non-existing0?test=2');
    $this->assertSession()->pageTextContains('non-existing0');
    $this->assertSession()->pageTextContains('non-existing1');
    $this->assertSession()->pageTextContains('non-existing2');
    $this->drupalGet('admin/config/search/redirect/404', ['query' => ['path' => 'g2']]);
    $this->assertSession()->pageTextNotContains('non-existing0?test=1');
    $this->assertSession()->pageTextNotContains('non-existing0?test=2');
    $this->assertSession()->pageTextNotContains('non-existing0');
    $this->assertSession()->pageTextNotContains('non-existing1');
    $this->assertSession()->pageTextContains('non-existing2');

    // Assign a redirect to 'non-existing2'.
    $this->clickLink('Add redirect');
    $expected_query = [
      'source' => 'non-existing2',
      'language' => 'en',
      'destination' => $destination,
    ];
    $parsed_url = UrlHelper::parse($this->getUrl());
    $this->assertEquals($parsed_url['path'], Url::fromRoute('redirect.add')->setAbsolute()->toString());
    $this->assertEquals($parsed_url['query'], $expected_query);
    $this->assertSession()->fieldValueEquals('redirect_source[0][path]', 'non-existing2');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->addressEquals('admin/config/search/redirect/404');
    $this->assertSession()->pageTextContains('non-existing0?test=1');
    $this->assertSession()->pageTextContains('non-existing0?test=2');
    $this->assertSession()->pageTextContains('non-existing0');
    $this->assertSession()->pageTextContains('non-existing1');
    $this->assertSession()->pageTextNotContains('non-existing2');
    // Check if the redirect works as expected.
    $this->drupalGet('admin/config/search/redirect');
    $this->assertSession()->pageTextContains('non-existing2');
  }

  /**
   * Tests the redirect ignore pages.
   */
  public function testIgnorePages() {
    // Create two nodes.
    $node1 = $this->drupalCreateNode(['type' => 'page']);
    $node2 = $this->drupalCreateNode(['type' => 'page']);

    // Set some pages to be ignored just for the test.
    $node_to_ignore = '/node/' . $node1->id() . '/test';
    $terms_to_ignore = '/term/*';
    $pages = $node_to_ignore . "\r\n" . $terms_to_ignore . "\n";
    \Drupal::configFactory()
      ->getEditable('redirect_404.settings')
      ->set('pages', $pages)
      ->save();

    // Visit ignored or non existing pages.
    $this->drupalGet('node/' . $node1->id() . '/test');
    $this->drupalGet('term/foo');
    $this->drupalGet('term/1');
    // Go to the "fix 404" page and check there are no 404 entries.
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertSession()->pageTextNotContains('node/' . $node1->id() . '/test');
    $this->assertSession()->pageTextNotContains('term/foo');
    $this->assertSession()->pageTextNotContains('term/1');

    // Visit non existing but 'unignored' page.
    $this->drupalGet('node/' . $node2->id() . '/test');
    // Go to the "fix 404" page and check there is a 404 entry.
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertSession()->pageTextContains('node/' . $node2->id() . '/test');

    // Add this 404 entry to the 'ignore path' list, assert it works properly.
    $path_to_ignore = '/node/' . $node2->id() . '/test';
    $destination = '&destination=admin/config/search/redirect/404';
    $this->clickLink('Ignore');
    $this->assertSession()->addressEquals('admin/config/search/redirect/settings?ignore=' . $path_to_ignore . $destination);
    $this->assertSession()->pageTextContains('Resolved the path ' . $path_to_ignore . ' in the database. Please check the ignored list and save the settings.');
    $this->assertSession()->fieldValueEquals('ignore_pages', $node_to_ignore . "\n/term/*\n/node/2/test");
    $this->assertSession()->elementContains('css', '#edit-ignore-pages', $node_to_ignore);
    $this->assertSession()->elementContains('css', '#edit-ignore-pages', $terms_to_ignore);
    $this->assertSession()->elementContains('css', '#edit-ignore-pages', $path_to_ignore);

    // Save the path with wildcard, but omitting the leading slash.
    $nodes_to_ignore = 'node/*';
    $edit = ['ignore_pages' => $nodes_to_ignore . "\r\n" . $terms_to_ignore];
    $this->submitForm($edit, 'Save configuration');
    // Should redirect to 'Fix 404'. Check the 404 entry is not shown anymore.
    $this->assertSession()->addressEquals('admin/config/search/redirect/404');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->assertSession()->pageTextNotContains('node/' . $node2->id() . '/test');
    $this->assertSession()->pageTextContains('There are no 404 errors to fix.');

    // Go back to the settings to check the 'Path to ignore' configurations.
    $this->drupalGet('admin/config/search/redirect/settings');
    $xpath = $this->xpath('//*[@id="edit-ignore-pages"]')[0]->getHtml();
    // Check that the new page to ignore has been saved with leading slash.
    $this->assertSession()->elementContains('css', '#edit-ignore-pages', '/' . $nodes_to_ignore);
    $this->assertSession()->elementContains('css', '#edit-ignore-pages', $terms_to_ignore);
    $this->assertSession()->elementNotContains('css', '#edit-ignore-pages', $node_to_ignore);
    $this->assertSession()->elementNotContains('css', '#edit-ignore-pages', $path_to_ignore);

    // Testing whitelines.
    $this->drupalGet('llama_page');
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertSession()->pageTextContains('llama_page');
    $this->clickLink('Ignore');
    $this->assertSession()->fieldValueEquals('ignore_pages', "/node/*\n/term/*\n/llama_page");
    $this->getSession()->getPage()->pressButton('Save configuration');
    $this->drupalGet('admin/config/search/redirect/settings');
    $this->assertSession()->fieldValueEquals('ignore_pages', "/node/*\n/term/*\n/llama_page");

    // Test clearing of ignored pages.
    $this->drupalGet('vicuna_page');
    $this->drupalGet('vicuna_page/subpage');
    $this->drupalGet('prefix/vicuna_page/subpage');
    $this->drupalGet('alpaca_page');
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertSession()->pageTextContains('vicuna_page');
    $this->assertSession()->pageTextContains('alpaca_page');
    $this->drupalGet('admin/config/search/redirect/settings');
    $edit = [
      'ignore_pages' => '*vicuna*',
      'clear_ignored' => TRUE,
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertSession()->pageTextNotContains('vicuna');

    $this->drupalGet('prefix/jaguar_page/subpage');
    $this->drupalGet('prefix/tucan_page/subpage');
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertSession()->pageTextContains('jaguar_page');
    $this->assertSession()->pageTextContains('tucan_page');
    $this->drupalGet('admin/config/search/redirect/settings');
    $edit = [
      'ignore_pages' => '*/tucan_page/*',
      'clear_ignored' => TRUE,
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertSession()->pageTextContains('jaguar_page');
    $this->assertSession()->pageTextNotContains('tucan_page');
  }

  /**
   * Tests the redirect ignore pages for users without the 'administer redirect settings' permission.
   */
  public function testIgnorePagesNonAdmin() {
    // Create a node.
    $node = $this->drupalCreateNode(['type' => 'page']);
    $this->container->get('config.factory')
      ->getEditable('redirect_404.settings')
      ->set('pages', "/brian\n/flying/circus\n/meaning/of/*\n")
      ->save();

    // Create a non admin user.
    $user = $this->drupalCreateUser([
      'administer redirects',
      'ignore 404 requests',
      'access content',
      'bypass node access',
      'create url aliases',
      'administer url aliases',
    ]);
    $this->drupalLogin($user);

    // Visit non existing pages.
    $this->drupalGet('node/' . $node->id() . '/foobar');
    // Go to the "fix 404" page and check there is a 404 entry.
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertSession()->pageTextContains('node/' . $node->id() . '/foobar');

    // Add this 404 entry to the 'ignore path' list, assert it works properly.
    $this->clickLink('Ignore');
    $this->assertSession()->addressEquals('admin/config/search/redirect/404');
    // Check the message.
    $this->assertSession()->pageTextContains('Resolved the path /node/' . $node->id() . '/foobar in the database.');
    // This removes the message.
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertSession()->pageTextNotContains('node/' . $node->id() . '/foobar');

    $config = $this->container->get('config.factory')
      ->get('redirect_404.settings')
      ->get('pages');
    $this->assertStringContainsString('node/' . $node->id() . '/foobar', $config);
  }

  /**
   * Tests the test_404_reset_submit button to remove all 404 entries.
   */
  public function test404ResetSubmit() {
    // Go to non-existing paths:
    $this->drupalGet('non-existing0');
    $this->drupalGet('non-existing0?test=1');
    $this->drupalGet('non-existing0?test=2');
    $this->drupalGet('non-existing1');
    $this->drupalGet('non-existing2');
    // Go to the "Fix 404" page and check wheter these 404 entries exist:
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertSession()->pageTextContains('non-existing0?test=1');
    $this->assertSession()->pageTextContains('non-existing0?test=2');
    $this->assertSession()->pageTextContains('non-existing0');
    $this->assertSession()->pageTextContains('non-existing1');
    $this->assertSession()->pageTextContains('non-existing2');

    // Go to the "Settings" page, press the "Clear all 404 log entries" button:
    $this->drupalGet('admin/config/search/redirect/settings');
    $this->assertSession()->elementExists('css', '#edit-reset-404');
    $this->getSession()->getPage()->pressButton('Clear all 404 log entries');
    // Go to the "Fix 404" page and check wheter these 404 entries DO NOT exist:
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertSession()->pageTextNotContains('non-existing0?test=1');
    $this->assertSession()->pageTextNotContains('non-existing0?test=2');
    $this->assertSession()->pageTextNotContains('non-existing0');
    $this->assertSession()->pageTextNotContains('non-existing1');
    $this->assertSession()->pageTextNotContains('non-existing2');

    // Ensure new 404 entries are created after clearing:
    $this->drupalGet('non-existing0');
    $this->drupalGet('non-existing0?test=1');
    // Go to the "Fix 404" page and check wheter these 404 entries exist:
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertSession()->pageTextContains('non-existing0');
    $this->assertSession()->pageTextContains('non-existing0?test=1');
  }

}
