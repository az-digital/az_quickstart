<?php

namespace Drupal\Tests\paragraphs\Functional\WidgetStable;

use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the Paragraphs user interface.
 *
 * @group paragraphs
 */
class ParagraphsUiTest extends ParagraphsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = array(
    'content_translation',
    'image',
    'field',
    'field_ui',
    'block',
    'language',
    'node'
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    ConfigurableLanguage::create(['id' => 'de', 'label' => '1German'])->save();
    ConfigurableLanguage::create(['id' => 'fr', 'label' => '2French'])->save();
    $this->addParagraphedContentType('paragraphed_content_demo', 'field_paragraphs_demo');
    $this->loginAsAdmin([
      'administer site configuration',
      'administer content translation',
      'administer languages',
    ]);
    $this->addParagraphsType('nested_paragraph');
    $this->addParagraphsField('nested_paragraph', 'field_paragraphs_demo', 'paragraph');
    $this->addParagraphsType('images');
    static::fieldUIAddNewField('admin/structure/paragraphs_type/images', 'images_demo', 'Images', 'image', ['cardinality' => -1], ['settings[alt_field]' => FALSE]);
    $this->addParagraphsType('text_image');
    static::fieldUIAddNewField('admin/structure/paragraphs_type/text_image', 'image_demo', 'Images', 'image', ['cardinality' => -1], ['settings[alt_field]' => FALSE]);
    static::fieldUIAddNewField('admin/structure/paragraphs_type/text_image', 'text_demo', 'Text', 'text_long', [], []);
    $this->addParagraphsType('text');
    static::fieldUIAddExistingField('admin/structure/paragraphs_type/text', 'field_text_demo', 'Text', []);
    $edit = [
      'entity_types[node]' => TRUE,
      'entity_types[paragraph]' => TRUE,
      'settings[node][paragraphed_content_demo][translatable]' => TRUE,
      'settings[node][paragraphed_content_demo][fields][field_paragraphs_demo]' => FALSE,
      'settings[paragraph][images][translatable]' => TRUE,
      'settings[paragraph][text_image][translatable]' => TRUE,
      'settings[paragraph][text][translatable]' => TRUE,
      'settings[paragraph][nested_paragraph][translatable]' => TRUE,
      'settings[paragraph][nested_paragraph][fields][field_paragraphs_demo]' => FALSE,
      'settings[paragraph][nested_paragraph][settings][language][language_alterable]' => TRUE,
      'settings[paragraph][images][fields][field_images_demo]' => TRUE,
      'settings[paragraph][text_image][fields][field_image_demo]' => TRUE,
      'settings[paragraph][text_image][fields][field_text_demo]' => TRUE,
      'settings[node][paragraphed_content_demo][settings][language][language_alterable]' => TRUE
    ];
    $this->drupalGet('admin/config/regional/content-language');
    $this->submitForm($edit, 'Save configuration');
  }

  /**
   * Tests displaying an error message a required paragraph field that is empty.
   */
  public function testEmptyRequiredField() {
    $admin_user = $this->drupalCreateUser([
      'administer node fields',
      'administer paragraph form display',
      'administer node form display',
      'create paragraphed_content_demo content',
      'edit any paragraphed_content_demo content',
    ]);
    $this->drupalLogin($admin_user);

    // Add required field to paragraphed content type.
    $bundle_path = 'admin/structure/types/manage/paragraphed_content_demo';
    $field_title = 'Content Test';
    $field_type = 'field_ui:entity_reference_revisions:paragraph';
    $field_edit = [
      'required' => TRUE,
    ];
    $this->fieldUIAddNewField($bundle_path, 'content', $field_title, $field_type, [], $field_edit);

    $form_display_edit = [
      'fields[field_content][type]' => 'paragraphs',
    ];
    $this->drupalGet($bundle_path . '/form-display');
    $this->submitForm($form_display_edit, 'Save');

    // Attempt to create a paragraphed node with an empty required field.
    $title = 'Empty';
    $this->drupalGet('node/add/paragraphed_content_demo');
    $this->submitForm(['title[0][value]' => $title], 'Save');
    $this->assertSession()->pageTextContains($field_title . ' field is required');

    // Attempt to create a paragraphed node with only a paragraph in the
    // "remove" mode in the required field.
    $title = 'Remove all items';
    $this->drupalGet('node/add/paragraphed_content_demo');
    $this->submitForm([], 'field_content_text_image_add_more');
    $this->submitForm([], 'field_content_0_remove');
    $this->assertSession()->pageTextNotContains($field_title . ' field is required');
    $this->submitForm(['title[0][value]' => $title], 'Save');
    $this->assertSession()->pageTextContains($field_title . ' field is required');

    // Attempt to create a paragraphed node with a valid paragraph and a
    // removed paragraph.
    $title = 'Valid Removal';
    $this->drupalGet('node/add/paragraphed_content_demo');
    $this->submitForm([], 'field_content_text_image_add_more');
    $this->submitForm([], 'field_content_text_image_add_more');
    $this->submitForm([], 'field_content_1_remove');
    $this->assertSession()->pageTextNotContains($field_title . ' field is required');
    $this->submitForm(['title[0][value]' => $title], 'Save');
    $this->assertSession()->pageTextNotContains($field_title . ' field is required');
  }

}
