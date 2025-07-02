<?php

namespace Drupal\Tests\paragraphs_library\FunctionalJavascript;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\entity_browser\FunctionalJavascript\EntityBrowserWebDriverTestBase;
use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;

/**
 * Tests entity browser integration with paragraphs.
 *
 * @group paragraphs_library
 */
class ParagraphsLibraryItemEntityBrowserTest extends EntityBrowserWebDriverTestBase {

  use ParagraphsTestBaseTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'views',
    'block',
    'node',
    'file',
    'image',
    'field_ui',
    'views_ui',
    'system',
    'node',
    'paragraphs_library',
    'entity_browser',
    'content_translation'
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * Tests a flow of adding/removing references with paragraphs.
   */
  public function testEntityBrowserWidget() {
    ConfigurableLanguage::createFromLangcode('de')->save();
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->addParagraphedContentType('paragraphed_test', 'field_paragraphs');
    $admin = $this->drupalCreateUser([
      'create paragraphed_test content',
      'edit any paragraphed_test content',
      'administer paragraphs library',
      'administer entity browsers',
      'access paragraphs_library_items entity browser pages',
      'administer content translation',
      'translate any entity',
      'create content translations',
      'administer languages',
    ]);
    $this->drupalLogin($admin);

    // Make everything that is needed translatable.
    $this->drupalGet('admin/config/regional/content-language');
    $this->assertSession()->fieldExists('entity_types[paragraphs_library_item]')->check();
    // Open details for Content settings in Drupal 10.2.
    $ssummary = $this->getSession()->getPage()->find('css', '#edit-settings-paragraphs-library-item summary');
    if ($ssummary) {
      $ssummary->click();
    }
    $edit = [
      'settings[paragraphs_library_item][paragraphs_library_item][translatable]' => TRUE,
    ];
    $this->submitForm($edit, 'Save configuration');

    $this->addParagraphsType('text');
    $this->addFieldtoParagraphType('text', 'field_text', 'text');

    // Add a paragraph library item.
    $this->drupalGet('admin/content/paragraphs');
    $this->getSession()->getPage()->clickLink('Add library item');
    $element = $this->getSession()->getPage()->find('xpath', '//*[contains(@class, "dropbutton-toggle")]');
    $element->click();
    $button = $this->getSession()->getPage()->findButton('Add text');
    $button->press();
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->fillField('label[0][value]', 'test_library_item');
    $this->getSession()->getPage()->fillField('paragraphs[0][subform][field_text][0][value]', 'reusable_text');
    $this->submitForm([], 'Save');

    // Add a node with a paragraph from library.
    $this->drupalGet('node/add');
    $title = $this->assertSession()->fieldExists('Title');
    $title->setValue('Paragraph test');
    $this->getSession()->getPage()->pressButton('field_paragraphs_from_library_add_more');
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->pressButton('Select reusable paragraph');
    $this->waitForAjaxToFinish();
    $this->getSession()->switchToIFrame('entity_browser_iframe_paragraphs_library_items');
    $style_selector = $this->getSession()->getPage()->find('css', 'input[value="paragraphs_library_item:1"].form-radio');
    $style_selector->click();
    $this->assertSession()->buttonExists('Select reusable paragraph')->press();
    $this->getSession()->switchToIFrame();

    $this->waitForAjaxToFinish();
    $this->submitForm([], 'Save');
    // Check that the paragraph was correctly reused.
    $this->assertSession()->pageTextContains('reusable_text');

    // Translate the library item.
    $this->drupalGet('admin/content/paragraphs');
    $this->clickLink('test_library_item');
    $this->clickLink('Translate');
    $this->clickLink('Add');
    $edit = [
      'label[0][value]' => 'DE Title',
      'paragraphs[0][subform][field_text][0][value]' => 'DE Library text',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Paragraph DE Title has been updated.');

    // Add a node with a paragraph from library.
    $this->drupalGet('node/add');
    $title = $this->assertSession()->fieldExists('Title');
    $title->setValue('Paragraph test');
    $this->getSession()->getPage()->pressButton('field_paragraphs_from_library_add_more');
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->pressButton('Select reusable paragraph');
    $this->waitForAjaxToFinish();
    $this->getSession()->switchToIFrame('entity_browser_iframe_paragraphs_library_items');
    // Check that there is only one translation of the paragraph listed.
    $rows = $this->xpath('//*[@id="entity-browser-paragraphs-library-items-form"]/div[1]/div[2]/table/tbody/tr');
    $this->assertCount(1, $rows);

    // Add a text paragraph in a new library item.
    $this->drupalGet('admin/content/paragraphs/add/default');
    $element = $this->getSession()->getPage()->find('xpath', '//*[contains(@class, "dropbutton-toggle")]');
    $element->click();
    $button = $this->getSession()->getPage()->findButton('Add text');
    $button->press();
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->fillField('label[0][value]', 'Inner library item');
    $this->getSession()->getPage()->fillField('paragraphs[0][subform][field_text][0][value]', 'This is a reusable text.');
    $this->submitForm([], 'Save');
    // Add a library item inside a library item.
    $this->drupalGet('admin/content/paragraphs/add/default');
    $this->getSession()->getPage()->fillField('label[0][value]', 'Outside library item');
    $button = $this->getSession()->getPage()->findButton('Add From library');
    $button->press();
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->pressButton('Select reusable paragraph');
    $this->waitForAjaxToFinish();
    $this->getSession()->switchToIFrame('entity_browser_iframe_paragraphs_library_items');
    $style_selector = $this->getSession()->getPage()->find('css', 'input[value="paragraphs_library_item:2"].form-radio');
    $style_selector->click();
    $this->assertSession()->buttonExists('Select reusable paragraph')->press();
    $this->getSession()->switchToIFrame();
    $this->waitForAjaxToFinish();
    // Edit the inside library item after adding it.
    $this->getSession()->getPage()->pressButton('Edit');
    $this->waitForAjaxToFinish();
    $this->assertSession()->fieldExists('paragraphs[0][subform][field_text][0][value]');
    $this->getSession()->getPage()->fillField('paragraphs[0][subform][field_text][0][value]', 'This is a reusable text UPDATED.');
    $save_button = $this->assertSession()->elementExists('css', '.ui-dialog .ui-dialog-buttonset button');
    $save_button->press();
    $this->waitForAjaxToFinish();
    $this->assertSession()->elementContains('css', '.paragraphs-collapsed-description .paragraphs-content-wrapper', 'This is a reusable text UPDATED.');
    $this->submitForm([], 'Save');
    // Edit the outside library item.
    $this->getSession()->getPage()->clickLink('Outside library item');
    $this->getSession()->getPage()->clickLink('Edit');
    $this->assertSession()->elementContains('css', '.paragraphs-collapsed-description .paragraphs-content-wrapper', 'This is a reusable text UPDATED.');
    // Edit the inner library item and assert the fields and values.
    $this->getSession()->getPage()->pressButton('Edit');
    $this->waitForAjaxToFinish();
    $this->assertSession()->fieldExists('paragraphs[0][subform][field_text][0][value]');

    // Add a node with the outside library item.
    $this->drupalGet('node/add');
    $title = $this->assertSession()->fieldExists('Title');
    $title->setValue('Overlay node');
    $this->getSession()->getPage()->pressButton('Add From library');
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->pressButton('Select reusable paragraph');
    $this->waitForAjaxToFinish();
    $this->getSession()->switchToIFrame('entity_browser_iframe_paragraphs_library_items');
    $style_selector = $this->getSession()->getPage()->find('css', 'input[value="paragraphs_library_item:3"].form-radio');
    $this->assertTrue($style_selector->isVisible());
    $style_selector->click();
    $this->assertSession()->buttonExists('Select reusable paragraph')->press();
    $this->getSession()->switchToIFrame();
    $this->waitForAjaxToFinish();
    $this->assertSession()->elementContains('css', '.paragraphs-collapsed-description .paragraphs-content-wrapper', 'Inner library item');
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('paragraphed_test Overlay node has been created.');
    // Edit the node.
    $node = $this->getNodeByTitle('Overlay node');
    $this->drupalGet('node/' . $node->id() . '/edit');
    // Edit the Outside library item.
    $this->getSession()->getPage()->pressButton('Edit');
    $this->waitForAjaxToFinish();
    // Edit the inner library item and assert its fields.
    $modal_form = $this->getSession()->getPage()->find('css', '.ui-dialog .paragraphs-library-item-form');
    $save_button = $modal_form->find('css', '.edit-button');
    $save_button->press();
    $this->waitForAjaxToFinish();
    $this->assertSession()->fieldExists('paragraphs[0][subform][field_text][0][value]');
  }

}
