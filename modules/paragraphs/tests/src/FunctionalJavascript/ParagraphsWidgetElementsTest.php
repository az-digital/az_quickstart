<?php

namespace Drupal\Tests\paragraphs\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Test paragraphs widget elements.
 *
 * @group paragraphs
 */
class ParagraphsWidgetElementsTest extends WebDriverTestBase {

  use LoginAdminTrait;
  use ParagraphsTestBaseTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'paragraphs',
    'field',
    'field_ui',
    'block',
    'link',
    'text',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test paragraphs drag handler during translation.
   */
  public function testDragHandler() {
    $this->addParagraphedContentType('paragraphed_content_demo', 'field_paragraphs_demo');
    $this->addParagraphsType('text');
    $this->addFieldtoParagraphType('text', 'field_text_demo', 'text');
    $this->loginAsAdmin([
      'administer site configuration',
      'create paragraphed_content_demo content',
      'edit any paragraphed_content_demo content',
      'delete any paragraphed_content_demo content',
      'administer content translation',
      'translate any entity',
      'create content translations',
      'administer languages',
    ]);
    ConfigurableLanguage::createFromLangcode('sr')->save();

    // Enable translation for node.
    $this->drupalGet('admin/config/regional/content-language');
    $this->assertSession()->fieldExists('entity_types[node]')->check();
    $this->assertSession()->fieldExists('entity_types[paragraph]')->check();
    // Open details for Content settings in Drupal 10.2.
    $node_settings = $this->getSession()->getPage()->find('css', '#edit-settings-node summary');
    if ($node_settings) {
      $node_settings->click();
    }
    $paragraph_settings = $this->getSession()->getPage()->find('css', '#edit-settings-paragraph summary');
    if ($paragraph_settings) {
      $paragraph_settings->click();
    }

    $edit = [
      'settings[node][paragraphed_content_demo][translatable]' => TRUE,
      'settings[paragraph][text][translatable]' => TRUE,
      'settings[paragraph][text][settings][language][language_alterable]' => TRUE,
    ];
    $this->submitForm($edit, 'Save configuration');
    $settings = [
      'add_mode' => 'modal',
    ];
    $this->setParagraphsWidgetSettings('paragraphed_content_demo', 'field_paragraphs_demo', $settings);

    // Create a node and add a paragraph.
    $page = $this->getSession()->getPage();
    $this->drupalGet('node/add/paragraphed_content_demo');
    $page->pressButton('Add Paragraph');
    $paragraphs_dialog = $this->assertSession()->waitForElementVisible('css', 'div.ui-dialog');
    $paragraphs_dialog->pressButton('text');
    $this->assertSession()->assertWaitOnAjaxRequest();
    // Assert the draghandle is visible.
    $style_selector = $page->find('css', '.tabledrag-handle');
    $this->assertTrue($style_selector->isVisible());
    $edit = [
      'title[0][value]' => 'Title',
      'field_paragraphs_demo[0][subform][field_text_demo][0][value]' => 'First',
    ];
    $this->submitForm($edit, 'Save');
    // Translate the node.
    $node = $this->getNodeByTitle('Title');
    $this->drupalGet('node/' . $node->id() . '/translations/add/en/sr');
    $page = $this->getSession()->getPage();
    // Assert that the draghandle is not displayed.
    $this->assertEmpty($page->find('css', '.tabledrag-handle'));
  }

}
