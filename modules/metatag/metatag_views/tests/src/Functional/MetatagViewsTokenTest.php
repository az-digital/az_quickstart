<?php

namespace Drupal\Tests\metatag_views\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\metatag\Functional\MetatagHelperTrait;

/**
 * Confirm the tokenization functionality works.
 *
 * @group metatag
 */
class MetatagViewsTokenTest extends BrowserTestBase {

  // Contains helper methods.
  use MetatagHelperTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // Modules for core functionality.
    'block',
    'field',
    'field_ui',
    'help',
    'node',
    'user',

    // Views. Duh. Enable the Views UI so it can be fully tested.
    'views',
    'views_ui',

    // Contrib dependencies.
    'token',
    'metatag',

    // This module.
    'metatag_views',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * Confirm the Views tokenization functionality works, including UI.
   */
  public function testTokenization() {
    $this->loginUser1();
    $page_path = $this->randomMachineName();
    $this->drupalGet('/admin/structure/views/add');
    // @todo Also verify the form loads correctly.
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'label' => $this->randomString(),
      'id' => 'test',
      'page[create]' => 1,
      'page[path]' => $page_path,
      'page[style][row_plugin]' => 'titles',
    ];
    $this->submitForm($edit, 'Save and edit');
    $title_prefix = $this->updateView(TRUE);
    $node_title = $this->randomString();
    $this->createContentTypeNode($node_title);
    $this->drupalGet("/$page_path");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->titleEquals("$title_prefix $node_title");

    // Test caching by asserting a change of the View changes the page as well.
    $title_prefix = $this->updateView();
    $this->drupalGet("/$page_path");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->titleEquals("$title_prefix $node_title");

    // Reload the page and confirm the values persist.
    $this->drupalGet("/$page_path");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->titleEquals("$title_prefix $node_title");
  }

  /**
   * Update the view.
   *
   * @param bool $assert_ui
   *   Also assert the Views UI behaves correctly.
   *
   * @return string
   *   The title with its full prefix.
   */
  protected function updateView(bool $assert_ui = FALSE): string {
    $title_prefix = $this->randomMachineName();
    $edit = [
      'title' => $title_prefix . ' {{ title }}',
      'tokenize' => 1,
    ];
    $metatag_settings_path = '/admin/structure/views/nojs/display/test/page_1/metatags';
    $this->drupalGet($metatag_settings_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm($edit, 'Apply');
    // @todo Also verify the page contains the correct response.
    $this->assertSession()->statusCodeEquals(200);

    // Make sure the UI does not tokenize away {{ title }}.
    if ($assert_ui) {
      // Reload the form.
      $this->drupalGet($metatag_settings_path);
      $this->assertSession()->statusCodeEquals(200);
      $actual = $this->getSession()
        ->getPage()
        ->find('css', '#edit-title')
        ->getAttribute('value');
      $this->assertSame($edit['title'], $actual);
    }
    $this->drupalGet('/admin/structure/views/view/test');
    // @todo Also verify the page contains the correct response.
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm([], 'Save');
    // @todo Also verify the page contains the correct response.
    $this->assertSession()->statusCodeEquals(200);

    return $title_prefix;
  }

}
