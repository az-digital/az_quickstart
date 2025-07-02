<?php

namespace Drupal\Tests\smart_title\Functional;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;

/**
 * Tests the module's title hide functionality.
 *
 * @group smart_title
 */
class SmartTitleBasicTest extends SmartTitleBrowserTestBase {

  /**
   * Tests that Smart Title without config doesn't modifies core behavior.
   */
  public function testSmartTitleBasics() {
    $this->drupalLogin($this->adminUser);

    // Node teaser title was displayed on the front page for admin user.
    $this->drupalGet('node');
    $article_title = $this->xpath($this->cssSelectToXpath('article > h2'));
    $this->assertEquals($this->testPageNode->label(), $article_title[0]->getText());

    // Node title wasn't displayed on the node's full page for admin user.
    $this->drupalGet('node/' . $this->testPageNode->id());
    $article_title = $this->xpath($this->cssSelectToXpath('article > h2'));
    $this->assertEquals($article_title, []);

    $this->drupalLogout();

    // Node teaser title was displayed on the front page for anonymous user.
    $this->drupalGet('node');
    $article_title = $this->xpath($this->cssSelectToXpath('article > h2'));
    $this->assertEquals($this->testPageNode->label(), $article_title[0]->getText());

    // Node title wasn't displayed on the node's full page for anonymous user.
    $this->drupalGet('node/' . $this->testPageNode->id());
    $article_title = $this->xpath($this->cssSelectToXpath('article > h2'));
    $this->assertEquals($article_title, []);

    // Enable Smart Title for the test_page content type.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/structure/types/manage/test_page/display');
    $this->submitForm([
      'smart_title__enabled' => TRUE,
    ], 'Save');
    $this->submitForm([
      'fields[smart_title][weight]' => '-5',
      'fields[smart_title][region]' => 'content',
    ], 'Save');

    // Verify settings save.
    $display = $this->container->get('entity_type.manager')
      ->getStorage('entity_view_display')
      ->load('node.' . $this->testPageNode->getType() . '.default');
    assert($display instanceof EntityViewDisplayInterface);
    $smart_title_enabled = $display->getThirdPartySetting('smart_title', 'enabled');
    $saved_settings = $display->getThirdPartySetting('smart_title', 'settings');
    $this->assertTrue($smart_title_enabled);
    $this->assertEquals($saved_settings, [
      'smart_title__tag' => 'h2',
      'smart_title__classes' => ['node__title'],
      'smart_title__link' => TRUE,
    ]);

    // Verify cancelled settings save.
    $this->click('[name="smart_title_settings_edit"]');
    $page = $this->getSession()->getPage();
    $page->selectFieldOption('fields[smart_title][settings_edit_form][settings][smart_title__tag]', 'span');
    $page->fillField('fields[smart_title][settings_edit_form][settings][smart_title__classes]', 'test classes');
    $page->uncheckField('fields[smart_title][settings_edit_form][settings][smart_title__link]');
    $this->submitForm([], 'Cancel');

    $display = $this->container->get('entity_type.manager')
      ->getStorage('entity_view_display')
      ->load('node.' . $this->testPageNode->getType() . '.default');
    assert($display instanceof EntityViewDisplayInterface);
    $smart_title_enabled = $display->getThirdPartySetting('smart_title', 'enabled');
    $saved_settings = $display->getThirdPartySetting('smart_title', 'settings');
    $this->assertTrue($smart_title_enabled);
    $this->assertEquals($saved_settings, [
      'smart_title__tag' => 'h2',
      'smart_title__classes' => ['node__title'],
      'smart_title__link' => TRUE,
    ]);

    // Test that node teaser title isn't displayed on front page for admin user.
    $this->drupalGet('node');
    $web_assert = $this->assertSession();

    $web_assert->elementExists('css', 'article > h2');
    // Test that the expected settings are applied onto the title markup.
    $web_assert->elementNotExists('css', 'article > div > h2.node__title');

    $this->drupalLogout();

    // Test that node title is displayed for anonymous user.
    $this->drupalGet($this->testPageNode->toUrl());
    $web_assert = $this->assertSession();
    // Check page title.
    $this->assertSession()->titleEquals(strtr('@title | Drupal', ['@title' => $this->testPageNode->getTitle()]));
    // Check that title element exists.
    $web_assert->elementExists('css', 'article > div > h2.node__title');
    // Verify that smart title's link wraps the title field's output, so that
    // it is NOT inside the field element.
    $web_assert->elementExists('css', 'article > div > h2.node__title > a > span');
  }

}
