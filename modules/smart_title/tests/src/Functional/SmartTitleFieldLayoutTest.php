<?php

namespace Drupal\Tests\smart_title\Functional;

/**
 * Tests the module's title placement function.
 *
 * @group smart_title
 */
class SmartTitleFieldLayoutTest extends SmartTitleBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_layout',
  ];

  /**
   * Tests that Smart Title works properly with Field Layout.
   */
  public function testSmartTitlePlacement() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/structure/types/manage/test_page/display/teaser');

    // Enable Smart Title for test_page teaser display mode and make it visible.
    $this->submitForm([
      'smart_title__enabled' => TRUE,
    ], 'Save');

    // Change layout for teaser view mode.
    $form_edit = [
      'field_layout' => 'layout_twocol',
    ];
    $this->drupalGet('admin/structure/types/manage/test_page/display/teaser');
    $this->submitForm($form_edit, 'Change layout');
    $this->submitForm([], 'Save');

    // Make Smart Title visible for teaser view mode with custom configuration.
    $this->submitForm([
      'fields[smart_title][region]' => 'second',
    ], 'Save');
    $this->click('[name="smart_title_settings_edit"]');
    $this->submitForm([
      'fields[smart_title][settings_edit_form][settings][smart_title__tag]' => 'h3',
      'fields[smart_title][settings_edit_form][settings][smart_title__classes]' => 'smart-title--test',
    ], 'Save');

    // Test that Smart Title is displayed on the front page (teaser view mode)
    // in the corresponding field layout region for admin user.
    $this->drupalGet('node');
    $this->assertSession()->pageTextContains($this->testPageNode->label());
    $article_title = $this->xpath($this->cssSelectToXpath('article .layout__region--second h3.smart-title--test'));
    $this->assertEquals($this->testPageNode->label(), $article_title[0]->getText());

    // Default title isn't displayed on the front page for admin user.
    $this->drupalGet('node');
    $article_title = $this->xpath($this->cssSelectToXpath('article > h2'));
    $this->assertEquals($article_title, []);

    $this->drupalLogout();

    // Smart Title is displayed on the front page (teaser vm) in the
    // corresponding field layout region for anonymous user.
    $this->drupalGet('node');
    $this->assertSession()->pageTextContains($this->testPageNode->label());
    $article_title = $this->xpath($this->cssSelectToXpath('article .layout__region--second h3.smart-title--test'));
    $this->assertEquals($this->testPageNode->label(), $article_title[0]->getText());

    // Default title isn't displayed on the front page for anonymous user.
    $this->drupalGet('node');
    $article_title = $this->xpath($this->cssSelectToXpath('article > h2'));
    $this->assertEquals($article_title, []);
  }

}
