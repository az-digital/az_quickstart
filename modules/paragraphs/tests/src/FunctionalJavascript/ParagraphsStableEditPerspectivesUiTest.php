<?php

namespace Drupal\Tests\paragraphs\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\Tests\paragraphs\Traits\ParagraphsCoreVersionUiTestTrait;

/**
 * Test paragraphs user interface.
 *
 * @group paragraphs
 */
class ParagraphsStableEditPerspectivesUiTest extends WebDriverTestBase {

  use LoginAdminTrait;
  use ParagraphsTestBaseTrait;
  use ParagraphsCoreVersionUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'paragraphs_test',
    'paragraphs',
    'field',
    'field_ui',
    'block',
    'link',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->placeDefaultBlocks();
  }

  /**
   * Tests visibility of elements when switching perspectives.
   */
  public function testEditPerspectives() {
    $this->loginAsAdmin([
      'edit behavior plugin settings'
    ]);

    $page = $this->getSession()->getPage();
    $this->drupalGet('admin/structure/paragraphs_type/add');
    $page->fillField('label', 'TestPlugin');
    $this->assertSession()->waitForElementVisible('css', '#edit-name-machine-name-suffix .link');
    $page->pressButton('Edit');
    $page->fillField('id', 'testplugin');
    $page->checkField('behavior_plugins[test_text_color][enabled]');
    $page->pressButton('Save and manage fields');

    $this->addParagraphedContentType('testcontent', 'field_testparagraphfield');
    $this->addFieldtoParagraphType('testplugin', 'body', 'string_long');

    $this->drupalGet('node/add/testcontent');
    $this->clickLink('Behavior');
    $style_selector = $page->find('css', '.form-item-field-testparagraphfield-0-behavior-plugins-test-text-color-text-color');
    $this->assertTrue($style_selector->isVisible());
    $this->clickLink('Content');
    $this->assertFalse($style_selector->isVisible());

    // Assert scroll position when switching tabs.
    $this->getSession()->resizeWindow(800, 450);
    $this->drupalGet('node/add/testcontent');
    $button = $this->getSession ()->getPage()->findButton('Add TestPlugin');
    $button->press();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $button->press();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $button->press();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // First move to the last paragraph, assert that the tabs are
    // still visible, then move back up to the second.
    $this->getSession()->getPage()->find('css', '.field--widget-paragraphs tbody > tr:nth-child(4)')->mouseOver();
    $this->assertSession()->assertVisibleInViewport('css', '.paragraphs-tabs');
    $this->getSession()->getPage()->find('css', '.field--widget-paragraphs tbody > tr:nth-child(2)')->mouseOver();
    $this->getSession()->evaluateScript('window.scrollBy(0, -10);');

    // As a result, only paragraph 2 and 3 are fully visible on the content tab.
    $this->assertSession()->assertNotVisibleInViewport('css', '.field--widget-paragraphs tbody > tr:first-child');
    $this->assertSession()->assertVisibleInViewport('css', '.field--widget-paragraphs tbody > tr:nth-child(2)');
    $this->assertSession()->assertVisibleInViewport('css', '.field--widget-paragraphs tbody > tr:nth-child(3)');
    $this->assertSession()->assertNotVisibleInViewport('css', '.field--widget-paragraphs tbody > tr:nth-child(4)');
    $this->assertSession()->assertNotVisibleInViewport('css', '.field-add-more-submit');

    // When clicking the Behavior tab, paragraph 2, 3 and 4 are in the viewport
    // because the behavior settings take less space.
    $this->clickLink('Behavior');
    $this->assertSession()->assertVisibleInViewport('css', '.field--widget-paragraphs tbody > tr:nth-child(2)');
    $this->assertSession()->assertVisibleInViewport('css', '.field--widget-paragraphs tbody > tr:nth-child(3)');
    $this->assertSession()->assertVisibleInViewport('css', '.field--widget-paragraphs tbody > tr:nth-child(4)');

    // When we switch back to the Content tab, we should stay on the same
    // scroll position as before.
    $this->clickLink('Content');
    $this->assertSession()->assertNotVisibleInViewport('css', '.field--widget-paragraphs tbody > tr:first-child');
    $this->assertSession()->assertVisibleInViewport('css', '.field--widget-paragraphs tbody > tr:nth-child(2)');
    $this->assertSession()->assertVisibleInViewport('css', '.field--widget-paragraphs tbody > tr:nth-child(3)');
    $this->assertSession()->assertNotVisibleInViewport('css', '.field--widget-paragraphs tbody > tr:nth-child(4)');
    $this->assertSession()->assertNotVisibleInViewport('css', '.field-add-more-submit');
  }

  /**
   * Tests visibility of add modes actions when switching perspectives.
   */
  public function testPerspectivesAddModesVisibility() {
    $this->loginAsAdmin([
      'edit behavior plugin settings'
    ]);

    $page = $this->getSession()->getPage();
    $this->drupalGet('admin/structure/paragraphs_type/add');
    $page->fillField('label', 'TestPlugin');
    $this->assertSession()->waitForElementVisible('css', '#edit-name-machine-name-suffix .link');
    $page->pressButton('Edit');
    $page->fillField('id', 'testplugin');
    $page->checkField('behavior_plugins[test_text_color][enabled]');
    $page->pressButton('Save and manage fields');

    $this->addParagraphedContentType('testcontent', 'field_testparagraphfield');
    $this->addFieldtoParagraphType('testplugin', 'body', 'string_long');
    $form_display = \Drupal::service('entity_display.repository')->getFormDisplay('node', 'testcontent');
    $component = $form_display->getComponent('field_testparagraphfield');

    $component['settings']['add_mode'] = 'button';
    $form_display->setComponent('field_testparagraphfield', $component)->save();
    $this->drupalGet('node/add/testcontent');
    $add_wrapper = $page->find('css', '.paragraphs-add-wrapper');
    $this->assertTrue($add_wrapper->isVisible());
    $this->clickLink('Behavior');
    $this->assertFalse($add_wrapper->isVisible());

    $component['settings']['add_mode'] = 'select';
    $form_display->setComponent('field_testparagraphfield', $component)->save();
    $this->drupalGet('node/add/testcontent');
    $add_wrapper = $page->find('css', '.paragraphs-add-wrapper');
    $this->assertTrue($add_wrapper->isVisible());
    $this->clickLink('Behavior');
    $this->assertFalse($add_wrapper->isVisible());

    $component['settings']['add_mode'] = 'modal';
    $form_display->setComponent('field_testparagraphfield', $component)->save();
    $this->drupalGet('node/add/testcontent');
    $add_wrapper = $page->find('css', '.paragraphs-add-wrapper');
    $this->assertTrue($add_wrapper->isVisible());
    $this->clickLink('Behavior');
    $this->assertFalse($add_wrapper->isVisible());

    $component['settings']['add_mode'] = 'dropdown';
    $form_display->setComponent('field_testparagraphfield', $component)->save();
    $this->drupalGet('node/add/testcontent');
    $add_wrapper = $page->find('css', '.paragraphs-add-wrapper');
    $this->assertTrue($add_wrapper->isVisible());
    $this->clickLink('Behavior');
    $this->assertFalse($add_wrapper->isVisible());
  }

  /**
   * Test if tabs are visible with no behavior elements.
   */
  public function testTabsVisibility() {
    $this->loginAsAdmin([
      'access content overview',
    ]);

    $page = $this->getSession()->getPage();
    $this->drupalGet('admin/structure/paragraphs_type/add');
    $page->fillField('label', 'TestPlugin');
    $this->assertSession()->waitForElementVisible('css', '#edit-name-machine-name-suffix .link');
    $page->pressButton('Edit');
    $page->fillField('id', 'testplugin');
    $page->pressButton('Save and manage fields');

    $this->drupalGet('admin/structure/types/add');
    $page->fillField('name', 'TestContent');
    $this->assertSession()->waitForElementVisible('css', '#edit-name-machine-name-suffix .link');
    $page->pressButton('Edit');
    $page->fillField('type', 'testcontent');
    $page->pressButton('Save and manage fields');

    $this->drupalGet('admin/structure/types/manage/testcontent/fields/add-field');
    $page->selectFieldOption('new_storage_type', 'field_ui:entity_reference_revisions:paragraph');
    if ($this->coreVersion('10.3')) {
      $page->pressButton('Continue');
    }
    $page->fillField('label', 'testparagraphfield');
    $this->assertSession()->waitForElementVisible('css', '#edit-name-machine-name-suffix .link');
    $page->pressButton('Edit');
    $page->fillField('field_name', 'testparagraphfield');
    $page->pressButton('Continue');
    $edit = [
      'field_storage[subform][settings][target_type]' => 'paragraph',
    ];
    $this->submitForm($edit, 'Save settings');

    $this->drupalGet('node/add/testcontent');
    $style_selector = $page->find('css', '.paragraphs-tabs');
    $this->assertFalse($style_selector->isVisible());
  }

  /**
   * Test edit perspectives works fine with multiple fields.
   */
  public function testPerspectivesWithMultipleFields() {
    $this->loginAsAdmin([
      'edit behavior plugin settings'
    ]);

    // Add a nested Paragraph type.
    $paragraph_type = 'nested_paragraph';
    $this->addParagraphsType($paragraph_type);
    $this->addParagraphsField('nested_paragraph', 'paragraphs', 'paragraph');
    $edit = [
      'behavior_plugins[test_bold_text][enabled]' => TRUE,
    ];
    $this->drupalGet('admin/structure/paragraphs_type/' . $paragraph_type);
    $this->submitForm($edit, 'Save');

    $this->addParagraphedContentType('testcontent');
    $this->addParagraphsField('testcontent', 'field_paragraphs2', 'node');

    // Disable the default paragraph on both the node and the nested paragraph
    // to explicitly test with no paragraph and avoid a loop.
    EntityFormDisplay::load('node.testcontent.default')
      ->setComponent('field_paragraphs', ['type' => 'paragraphs', 'settings' => ['default_paragraph_type' => '_none']])
      ->setComponent('field_paragraphs2', ['type' => 'paragraphs', 'settings' => ['default_paragraph_type' => '_none']])
      ->save();
    EntityFormDisplay::load('paragraph' . '.' . $paragraph_type . '.default')
      ->setComponent('paragraphs', ['type' => 'paragraphs', 'settings' => ['default_paragraph_type' => '_none']])
      ->save();

    $assert_session = $this->assertSession();

    $this->drupalGet('node/add/testcontent');
    $assert_session->elementNotExists('css', '.paragraphs-nested');

    // Add a nested paragraph to the first field.
    $button = $this->getSession()->getPage()->findButton('Add nested_paragraph');
    $button->press();

    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementExists('css', '.paragraphs-nested');

    // Add a paragraph to the second field.
    $region_field2 = $this->getSession()->getPage()->find('css', '.field--name-field-paragraphs2');
    $button_field2 = $region_field2->findButton('Add nested_paragraph');
    $button_field2->press();
    $assert_session->assertWaitOnAjaxRequest();

    // Ge the style checkboxes from each field, make sure they are not visible
    // by default.
    $page = $this->getSession()->getPage();
    $style_selector = $page->findField('field_paragraphs[0][behavior_plugins][test_bold_text][bold_text]');
    $this->assertFalse($style_selector->isVisible());
    $style_selector2 = $page->findField('field_paragraphs2[0][behavior_plugins][test_bold_text][bold_text]');
    $this->assertFalse($style_selector2->isVisible());

    // Switch to Behavior on the first field, then the second, make sure
    // the visibility of the checkboxes is correct after each change.
    $this->clickLink('Behavior', 0);
    $this->assertTrue($style_selector->isVisible());
    $this->assertFalse($style_selector2->isVisible());
    $this->clickLink('Behavior', 1);
    $this->assertTrue($style_selector->isVisible());
    $this->assertTrue($style_selector2->isVisible());

    // Switch the second field back to Content, verify visibility again.
    $this->clickLink('Content', 1);
    $this->assertTrue($style_selector->isVisible());
    $this->assertFalse($style_selector2->isVisible());
  }

}
