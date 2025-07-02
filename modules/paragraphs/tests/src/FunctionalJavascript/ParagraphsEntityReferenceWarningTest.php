<?php

namespace Drupal\Tests\paragraphs\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\NodeType;

/**
 * Tests paragraphs customization for entity reference fields.
 *
 * @group paragraphs
 */
class ParagraphsEntityReferenceWarningTest extends WebDriverTestBase {

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
   * Tests paragraphs customization for entity reference fields.
   */
  public function testEntityReferenceTargetTypeWarning() {
    // Create the content type.
    $node_type = NodeType::create([
      'type' => 'example',
      'name' => 'example',
    ]);
    $node_type->save();
    $this->loginAsAdmin();

    $this->drupalGet('admin/structure/types/manage/example/fields/add-field');
    $page = $this->getSession()->getPage();
    $page->find('css', "[name='new_storage_type'][value='reference']")->getParent()->click();
    if ($this->coreVersion('10.3')) {
      $page->pressButton('Continue');
    }
    else {
      $this->assertSession()->assertWaitOnAjaxRequest();
    }
    $page->fillField('label', 'unsupported field');
    $page->find('css', "[name='group_field_options_wrapper'][value='entity_reference']")->getParent()->click();
    if (!$this->coreVersion('10.3')) {
      $this->assertSession()->assertWaitOnAjaxRequest();
    }
    $page->pressButton('Continue');

    $this->assertSession()->pageTextNotContains('Note: Regular paragraph fields should use the revision based reference fields, entity reference fields should only be used for cases when an existing paragraph is referenced from somewhere else.');
    $page->selectFieldOption('field_storage[subform][settings][target_type]', 'paragraph');

    $this->assertSession()->pageTextContains('Note: Regular paragraph fields should use the revision based reference fields, entity reference fields should only be used for cases when an existing paragraph is referenced from somewhere else.');

  }

}
