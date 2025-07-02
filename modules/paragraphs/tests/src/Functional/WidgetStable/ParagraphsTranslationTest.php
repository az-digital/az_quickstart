<?php

namespace Drupal\Tests\paragraphs\Functional\WidgetStable;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\Role;

/**
 * Tests the configuration of paragraphs.
 *
 * @group paragraphs
 */
class ParagraphsTranslationTest extends ParagraphsTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = array(
    'content_translation',
    'link',
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
      'create paragraphed_content_demo content',
      'edit any paragraphed_content_demo content',
      'delete any paragraphed_content_demo content',
      'administer content translation',
      'translate any entity',
      'create content translations',
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
      'settings[paragraph][text][fields][field_text_demo]' => TRUE,
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

    if (version_compare(\Drupal::VERSION, '8.4', '>=')) {
      // @todo Workaround for file usage/unable to save the node with no usages.
      //   Remove when https://www.drupal.org/node/2801777 is fixed.
      \Drupal::configFactory()->getEditable('file.settings')
        ->set('make_unused_managed_files_temporary', TRUE)
        ->save();
    }
  }

  /**
   * Tests the paragraph translation.
   */
  public function testParagraphTranslation() {
    // We need to add a permission to administer roles to deal with revisions.
    $roles = $this->loggedInUser->getRoles();
    $this->grantPermissions(Role::load(array_shift($roles)), ['administer nodes']);
    $this->drupalGet('admin/config/regional/content-language');

    // Check the settings are saved correctly.
    $this->assertSession()->checkboxChecked('edit-entity-types-paragraph');
    $this->assertSession()->checkboxChecked('edit-settings-node-paragraphed-content-demo-translatable');
    $this->assertSession()->checkboxChecked('edit-settings-paragraph-text-image-translatable');
    $this->assertSession()->checkboxChecked('edit-settings-paragraph-images-columns-field-images-demo-alt');
    $this->assertSession()->checkboxChecked('edit-settings-paragraph-images-columns-field-images-demo-title');

    // Check if the publish/unpublish option works.
    $this->drupalGet('admin/structure/paragraphs_type/text_image/form-display');
    $edit = array(
      'fields[status][type]' => 'boolean_checkbox',
      'fields[status][region]' => 'content',
    );
    // Use the stable widget.
    $form_display = EntityFormDisplay::load('node.paragraphed_content_demo.default')
      ->setComponent('field_paragraphs_demo', [
        'type' => 'paragraphs',
      ]);
    $form_display->save();
    // Use the stable widget.
    $form_display = EntityFormDisplay::load('paragraph.nested_paragraph.default')
      ->setComponent('field_paragraphs_demo', [
        'type' => 'paragraphs',
      ]);
    $form_display->save();
    $this->submitForm($edit, 'Save');
    $this->drupalGet('node/add/paragraphed_content_demo');
    $this->submitForm([], 'Add text_image');
    $this->assertSession()->responseContains('edit-field-paragraphs-demo-0-subform-status-value');
    $edit = [
      'title[0][value]' => 'example_publish_unpublish',
      'field_paragraphs_demo[0][subform][field_text_demo][0][value]' => 'Example published and unpublished',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Example published and unpublished');
    $this->clickLink('Edit');

    $this->submitForm([], 'field_paragraphs_demo_nested_paragraph_add_more');
    $this->submitForm([], 'field_paragraphs_demo_1_subform_field_paragraphs_demo_text_add_more');
    $edit = [
      'field_paragraphs_demo[0][subform][status][value]' => FALSE,
      'field_paragraphs_demo[1][subform][field_paragraphs_demo][0][subform][field_text_demo][0][value]' => 'Dummy text'
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextNotContains('Example published and unpublished');

    // Check the parent fields are set properly. Get the node.
    $node = $this->drupalGetNodeByTitle('example_publish_unpublish');
    // Loop over the paragraphs of the node.
    foreach ($node->field_paragraphs_demo->referencedEntities() as $paragraph) {
      $node_paragraph = Paragraph::load($paragraph->id())->toArray();
      // Check if the fields are set properly.
      $this->assertEquals($node_paragraph['parent_id'][0]['value'], $node->id());
      $this->assertEquals($node_paragraph['parent_type'][0]['value'], 'node');
      $this->assertEquals($node_paragraph['parent_field_name'][0]['value'], 'field_paragraphs_demo');
      // If the paragraph is nested type load the child.
      if ($node_paragraph['type'][0]['target_id'] == 'nested_paragraph') {
        $nested_paragraph = Paragraph::load($node_paragraph['field_paragraphs_demo'][0]['target_id'])->toArray();
        // Check if the fields are properly set.
        $this->assertEquals($nested_paragraph['parent_id'][0]['value'], $paragraph->id());
        $this->assertEquals($nested_paragraph['parent_type'][0]['value'], 'paragraph');
        $this->assertEquals($nested_paragraph['parent_field_name'][0]['value'], 'field_paragraphs_demo');
      }
    }

    // Add paragraphed content.
    $this->drupalGet('node/add/paragraphed_content_demo');
    $this->submitForm([], 'Add text_image');
    $edit = array(
      'title[0][value]' => 'Title in english',
      'field_paragraphs_demo[0][subform][field_text_demo][0][value]' => 'Text in english',
    );
    // The button to remove a paragraph is present.
    $this->assertSession()->responseContains('Remove');
    $this->submitForm($edit, 'Save');
    $node = $this->drupalGetNodeByTitle('Title in english');
    // The text is present when editing again.
    $this->clickLink('Edit');
    $this->assertSession()->pageTextContains('Title in english');
    $this->assertSession()->pageTextContains('Text in english');

    // Add french translation.
    $this->clickLink('Translate');
    $this->clickLink('Add', 1);
    // Make sure the Add / Remove paragraph buttons are hidden.
    $this->assertSession()->responseNotContains('Remove');
    $this->assertSession()->responseNotContains('Add text_image');
    // Make sure that the original paragraph text is displayed.
    $this->assertSession()->pageTextContains('Text in english');

    $edit = array(
      'title[0][value]' => 'Title in french',
      'field_paragraphs_demo[0][subform][field_text_demo][0][value]' => 'Text in french',
      'revision' => TRUE,
      'revision_log[0][value]' => 'french 1',
    );
    $this->submitForm($edit, 'Save (this translation)');
    $this->assertSession()->pageTextContains('paragraphed_content_demo Title in french has been updated.');

    // Check the english translation.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextContains('Title in english');
    $this->assertSession()->pageTextContains('Text in english');
    $this->assertSession()->pageTextNotContains('Title in french');
    $this->assertSession()->pageTextNotContains('Text in french');

    // Check the french translation.
    $this->drupalGet('fr/node/' . $node->id());
    $this->assertSession()->pageTextContains('Title in french');
    $this->assertSession()->pageTextContains('Text in french');
    $this->assertSession()->pageTextNotContains('Title in english');
    // The translation is still present when editing again.
    $this->clickLink('Edit');
    $this->assertSession()->pageTextContains('Title in french');
    $this->assertSession()->pageTextContains('Text in french');
    $edit = array(
      'title[0][value]' => 'Title Change in french',
      'field_paragraphs_demo[0][subform][field_text_demo][0][value]' => 'New text in french',
      'revision' => TRUE,
      'revision_log[0][value]' => 'french 2',
    );
    $this->submitForm($edit, 'Save (this translation)');
    $this->assertSession()->pageTextContains('Title Change in french');
    $this->assertSession()->pageTextContains('New text in french');

    // Back to the source language.
    $this->drupalGet('node/' . $node->id());
    $this->clickLink('Edit');
    $this->assertSession()->pageTextContains('Title in english');
    $this->assertSession()->pageTextContains('Text in english');
    // Save the original content on second request.
    $this->submitForm([], 'Save (this translation)');
    $this->assertSession()->pageTextContains('paragraphed_content_demo Title in english has been updated.');

    // Test if reverting to old paragraphs revisions works, make sure that
    // the reverted node can be saved again.
    $this->drupalGet('fr/node/' . $node->id() . '/revisions');
    $this->clickLink('Revert');
    $this->submitForm(['revert_untranslated_fields' => TRUE], 'Revert');
    $this->clickLink('Edit');
    $this->assertSession()->responseContains('Title in french');
    $this->assertSession()->pageTextContains('Text in french');
    $this->submitForm([], 'Save (this translation)');
    $this->assertSession()->responseNotContains('The content has either been modified by another user, or you have already submitted modifications');
    $this->assertSession()->pageTextContains('Text in french');

    //Add paragraphed content with untranslatable language
    $this->drupalGet('node/add/paragraphed_content_demo');
    $edit = array('langcode[0][value]' => LanguageInterface::LANGCODE_NOT_SPECIFIED);
    $this->submitForm($edit, 'Add text_image');
    $this->assertSession()->statusCodeEquals(200);

    // Make 'Images' paragraph field translatable, enable alt and title fields.
    $this->drupalGet('admin/structure/paragraphs_type/images/fields');
    $this->clickLink('Edit');
    $edit = [
      'translatable' => 1,
      'settings[alt_field]' => 1,
      'settings[title_field]' => 1,
    ];
    $this->submitForm($edit, 'Save settings');

    // Create a node with an image paragraph, its alt and title text.
    $files = $this->getTestFiles('image');
    $file_system = \Drupal::service('file_system');
    $file_path = $file_system->realpath($file_system->realpath($files[0]->uri));
    $this->drupalGet('node/add/paragraphed_content_demo');
    $this->submitForm([], 'Add images');
    $this->submitForm(['files[field_paragraphs_demo_0_subform_field_images_demo_0][]' => $file_path], 'Upload');
    $edit = [
      'title[0][value]' => 'Title EN',
      'field_paragraphs_demo[0][subform][field_images_demo][0][alt]' => 'Image alt',
      'field_paragraphs_demo[0][subform][field_images_demo][0][title]' => 'Image title',
    ];
    $this->submitForm($edit, 'Save');

    // Translate the node with the image paragraph.
    $this->clickLink('Translate');
    $this->clickLink('Add', 1);
    $edit = [
      'title[0][value]' => 'Title FR',
      'field_paragraphs_demo[0][subform][field_images_demo][0][alt]' => 'Image alt FR',
      'field_paragraphs_demo[0][subform][field_images_demo][0][title]' => 'Image title FR',
    ];
    $this->submitForm($edit, 'Save (this translation)');
    $this->assertSession()->responseContains('Title FR');

    $this->drupalGet('node/add/paragraphed_content_demo');
    $this->submitForm([], 'Add text');
    $edit = [
      'field_paragraphs_demo[0][subform][field_text_demo][0][value]' => 'texto',
      'title[0][value]' => 'titulo',
      'langcode[0][value]' => 'de',
    ];
    $this->submitForm($edit, 'Save');
    $node = $this->drupalGetNodeByTitle('titulo');
    $this->assertParagraphsLangcode($node->id(), 'de');

    // Test langcode matching when Paragraphs and node have different language.
    $paragraph_1 = Paragraph::create([
      'title' => 'Paragraph',
      'type' => 'text',
      'langcode' => 'en',
      'field_text_demo' => 'english_text_1',
    ]);
    $paragraph_1->save();

    $paragraph_2 = Paragraph::create([
      'title' => 'Paragraph',
      'type' => 'text',
      'langcode' => 'en',
      'field_text_demo' => 'english_text_2',
    ]);
    $paragraph_2->save();

    $paragraph_data = $paragraph_2->toArray();
    $paragraph_data['field_text_demo'] = 'german_text_2';
    $paragraph_2->addTranslation('de', $paragraph_data);
    $paragraph_2->save();
    $translated_paragraph = $paragraph_2->getTranslation('en');

    $node = $this->createNode([
      'langcode' => 'de',
      'type' => 'paragraphed_content_demo',
      'field_paragraphs_demo' => [$paragraph_1, $translated_paragraph],
    ]);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('paragraphed_content_demo ' . $node->label() . ' has been updated.');
    // Check that first paragraph langcode has been updated.
    \Drupal::entityTypeManager()->getStorage('paragraph')->resetCache([$paragraph_1->id(), $paragraph_2->id()]);
    $paragraph = Paragraph::load($paragraph_1->id());
    $this->assertEquals($paragraph->language()->getId(), 'de');
    $this->assertFalse($paragraph->hasTranslation('en'));
    // Check that second paragraph has two translations.
    $paragraph = Paragraph::load($paragraph_2->id());
    $this->assertTrue($paragraph->hasTranslation('de'));
    $this->assertTrue($paragraph->hasTranslation('en'));
    $this->assertSession()->responseContains('german_text');

    // Create an english translation of the node.
    $edit = [
      'field_paragraphs_demo[0][subform][field_text_demo][0][value]' => 'english_translation_1',
      'field_paragraphs_demo[1][subform][field_text_demo][0][value]' => 'english_translation_2',
    ];
    $this->drupalGet('node/' . $node->id() . '/translations/add/de/en');
    $this->submitForm($edit, 'Save (this translation)');
    // Attempt to create a french translation.
    $this->drupalGet('node/' . $node->id() . '/translations/add/de/fr');
    // Check that the german translation of the paragraphs is displayed.
    $this->assertSession()->fieldValueEquals('field_paragraphs_demo[0][subform][field_text_demo][0][value]', 'english_text_1');
    $this->assertSession()->fieldValueEquals('field_paragraphs_demo[1][subform][field_text_demo][0][value]', 'german_text_2');
    $this->submitForm(['source_langcode[source]' => 'en'], 'Change');
    // Check that the english translation of the paragraphs is displayed.
    $this->assertSession()->fieldValueEquals('field_paragraphs_demo[0][subform][field_text_demo][0][value]', 'english_translation_1');
    $this->assertSession()->fieldValueEquals('field_paragraphs_demo[1][subform][field_text_demo][0][value]', 'english_translation_2');

    // Create a node with empty Paragraphs.
    $this->drupalGet('node/add/paragraphed_content_demo');
    $this->submitForm([], 'Add nested_paragraph');
    $edit = ['title[0][value]' => 'empty_node'];
    $this->submitForm($edit, 'Save');
    // Attempt to translate it.
    $this->clickLink('Translate');
    $this->clickLink('Add');
    // Check the add button is not displayed.
    $this->assertEquals(count($this->xpath('//*[@name="field_paragraphs_demo_0_subform_field_paragraphs_demo_images_add_more"]')), 0);

    // Add a non translatable field to Text Paragraph type.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/text', 'untranslatable_field', 'untranslatable_field', 'text_long', [], []);

    // Add a non translatable reference field.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/text', 'untranslatable_ref_field', 'untranslatable_ref_field', 'field_ui:entity_reference:node', [], ['settings[handler_settings][target_bundles][paragraphed_content_demo]' => TRUE]);

    // Add a non translatable link field.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/text', 'untranslatable_link_field', 'untranslatable_link_field', 'link', [], []);

    // Attempt to add a translation.
    $this->drupalGet('node/' . $node->id() . '/translations/add/de/fr');
    $this->assertSession()->pageTextContains('untranslatable_field (all languages)');
    $this->assertSession()->pageTextContains('untranslatable_ref_field (all languages)');
    $this->assertSession()->pageTextContains('untranslatable_link_field (all languages)');
    $this->assertSession()->pageTextNotContains('Text (all languages)');

    // Enable translations for the reference and link field.
    $edit = [
      'translatable' => TRUE,
    ];
    $this->drupalGet('admin/structure/paragraphs_type/text/fields/paragraph.text.field_untranslatable_ref_field');
    $this->submitForm($edit, 'Save settings');
    $this->drupalGet('admin/structure/paragraphs_type/text/fields/paragraph.text.field_untranslatable_link_field');
    $this->submitForm($edit, 'Save settings');

    // Attempt to add a translation.
    $this->drupalGet('node/' . $node->id() . '/translations/add/de/fr');
    $this->assertSession()->pageTextContains('untranslatable_field (all languages)');
    $this->assertSession()->pageTextNotContains('untranslatable_link_field (all languages)');
    $this->assertSession()->pageTextNotContains('untranslatable_ref_field (all languages)');
    $this->assertSession()->pageTextNotContains('Text (all languages)');

    // Test the summary in multilingual scenarios.
    // Case 1: Nested Paragraphs.
    $this->setParagraphsWidgetSettings('paragraphed_content_demo', 'field_paragraphs_demo', ['edit_mode' => 'closed']);
    // Create a node with a text and a nested paragraph.
    $this->drupalGet('node/add/paragraphed_content_demo');
    $this->submitForm([], 'Add text');
    $this->submitForm([], 'Add nested_paragraph');
    $this->submitForm([], 'field_paragraphs_demo_1_subform_field_paragraphs_demo_text_add_more');
    $edit = [
      'title[0][value]' => 'EN llama',
      'langcode[0][value]' => 'en',
      'field_paragraphs_demo[0][subform][field_text_demo][0][value]' => 'EN text llama',
      'field_paragraphs_demo[1][subform][field_paragraphs_demo][0][subform][field_text_demo][0][value]' => 'EN nested text llama',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('paragraphed_content_demo EN llama has been created.');
    // Create a german translation.
    $node = $this->drupalGetNodeByTitle('EN llama');
    $this->drupalGet('node/' . $node->id() . '/translations/add/en/de');
    $this->submitForm([], 'field_paragraphs_demo_edit_all');
    $edit = [
      'title[0][value]' => 'DE llama',
      'field_paragraphs_demo[0][subform][field_text_demo][0][value]' => 'DE text llama',
      'field_paragraphs_demo[1][subform][field_paragraphs_demo][0][subform][field_text_demo][0][value]' => 'DE nested text llama',
    ];
    $this->submitForm($edit, 'Save (this translation)');
    // Assert that the summary is displayed in the current language.
    $this->drupalGet('de/node/' . $node->id() . '/edit');
    $this->assertSession()->fieldValueEquals('title[0][value]', 'DE llama');
    $this->assertSession()->responseContains('<span class="summary-content">DE text llama</span></div></div>');
    $this->assertSession()->responseContains('<span class="summary-content">DE nested text llama</span></div></div>');

    // Case 2: Referenced entities.
    $this->addParagraphsType('node_reference');
    static::fieldUIAddNewField('admin/structure/paragraphs_type/node_reference', 'entity_reference', 'Entity reference', 'entity_reference', [
      'settings[target_type]' => 'node',
      'cardinality' => '-1'
    ], [
      'settings[handler_settings][target_bundles][paragraphed_content_demo]' => TRUE,
    ]);
    // Add a node with a reference paragraph.
    $this->drupalGet('node/add/paragraphed_content_demo');
    $this->submitForm([], 'Add node_reference');
    $edit = [
      'title[0][value]' => 'EN referencing llama',
      'langcode[0][value]' => 'en',
      'field_paragraphs_demo[0][subform][field_entity_reference][0][target_id]' => $node->label() . ' (' . $node->id() . ')',
    ];
    $this->submitForm($edit, 'Save');
    $referencing_node = $this->drupalGetNodeByTitle('EN referencing llama');
    // Translate the node.
    $this->drupalGet('node/' . $referencing_node->id() . '/translations/add/en/de');
    $edit = [
      'title[0][value]' => 'DE referencing llama',
    ];
    $this->submitForm($edit, 'Save (this translation)');
    // Edit the node again and check the paragraph summary.
    $this->drupalGet('de/node/' . $referencing_node->id() . '/edit');
    $this->assertSession()->responseContains('<span class="summary-content">DE llama</span></div></div>');
  }

  /**
   * Tests the paragraph buttons presence in translation multilingual workflow.
   *
   * This test covers the following test cases:
   * 1) original node langcode in EN, translate in FR, change to DE.
   * 2) original node langcode in DE, change site langcode to DE, change node
   *    langcode to EN.
   */
  public function testParagraphTranslationMultilingual() {
    // Case 1: original node langcode in EN, translate in FR, change to DE.

    // Add 'Images' paragraph and check the paragraphs buttons are displayed.
    // Use the stable widget.
    $form_display = EntityFormDisplay::load('node.paragraphed_content_demo.default')
      ->setComponent('field_paragraphs_demo', [
        'type' => 'paragraphs',
      ]);
    $form_display->save();
    // Use the stable widget.
    $form_display = EntityFormDisplay::load('paragraph.nested_paragraph.default')
      ->setComponent('field_paragraphs_demo', [
        'type' => 'paragraphs',
      ]);
    $form_display->save();
    $this->drupalGet('node/add/paragraphed_content_demo');
    $this->submitForm([], 'Add images');
    $this->assertParagraphsButtons(1);
    // Upload an image and check the paragraphs buttons are still displayed.
    $images = $this->getTestFiles('image')[0];
    $edit = [
      'title[0][value]' => 'Title in english',
      'files[field_paragraphs_demo_0_subform_field_images_demo_0][]' => $images->uri,
    ];
    $this->submitForm($edit, 'Upload');
    $this->assertParagraphsButtons(1);
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('Title in english');
    $node = $this->drupalGetNodeByTitle('Title in english');
    // Check the paragraph langcode is 'en'.
    $this->assertParagraphsLangcode($node->id());

    // Add french translation.
    $this->clickLink('Translate');
    $this->clickLink('Add', 1);
    // Make sure the host entity and its paragraphs have valid source language
    // and check that the paragraphs buttons are hidden.
    $this->assertNoParagraphsButtons(1);
    $edit = [
      'title[0][value]' => 'Title in french',
    ];
    $this->submitForm($edit, 'Save (this translation)');
    $this->assertParagraphsLangcode($node->id(), 'en', 'fr');
    $this->assertSession()->pageTextContains('paragraphed_content_demo Title in french has been updated.');
    $this->assertSession()->pageTextContains('Title in french');
    $this->assertSession()->pageTextNotContains('Title in english');
    // Check the original node and the paragraph langcode is still 'en'.
    $this->assertParagraphsLangcode($node->id());

    // Edit the french translation and upload a new image.
    $this->clickLink('Edit');
    $images = $this->getTestFiles('image')[1];
    $this->submitForm([
      'files[field_paragraphs_demo_0_subform_field_images_demo_1][]' => $images->uri,
    ], 'Upload');
    // Check editing a translation does not affect the source langcode and
    // check that the paragraphs buttons are still hidden.
    $this->assertParagraphsLangcode($node->id(), 'en', 'fr');
    $this->assertNoParagraphsButtons(1);
    $this->submitForm([], 'Save (this translation)');
    $this->assertSession()->pageTextContains('Title in french');
    $this->assertSession()->pageTextNotContains('Title in english');

    // Back to the original node.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextContains('Title in english');
    $this->assertSession()->pageTextNotContains('Title in french');
    // Check the original node and the paragraph langcode are still 'en' and
    // check that the paragraphs buttons are still displayed.
    $this->clickLink('Edit');
    $this->assertParagraphsLangcode($node->id());
    $this->assertParagraphsButtons(1);
    // Change the node langcode to 'german', add a 'Nested Paragraph', check
    // the paragraphs langcode are still 'en' and their buttons are displayed.
    $edit = [
      'title[0][value]' => 'Title in english (de)',
      'langcode[0][value]' => 'de',
    ];
    $this->submitForm($edit, 'Add nested_paragraph');
    $this->assertParagraphsLangcode($node->id());
    $this->assertParagraphsButtons(2);
    // Add an 'Images' paragraph inside the nested one, check the paragraphs
    // langcode are still 'en' and the paragraphs buttons are still displayed.
    $this->submitForm([], 'field_paragraphs_demo_1_subform_field_paragraphs_demo_images_add_more');
    $this->assertParagraphsLangcode($node->id());
    $this->assertParagraphsButtons(2);
    // Upload a new image, check the paragraphs langcode are still 'en' and the
    // paragraphs buttons are displayed.
    $images = $this->getTestFiles('image')[2];
    $this->submitForm([
      'files[field_paragraphs_demo_1_subform_field_paragraphs_demo_0_subform_field_images_demo_0][]' => $images->uri,
    ], 'Upload');
    $this->assertParagraphsLangcode($node->id());
    $this->assertParagraphsButtons(2);
    $this->submitForm([], 'Save (this translation)');
    $this->assertSession()->pageTextContains('Title in english (de)');
    $this->assertSession()->pageTextNotContains('Title in french');
    // Check the original node and the paragraphs langcode are now 'de'.
    $this->assertParagraphsLangcode($node->id(), 'de');

    // Check the french translation.
    $this->drupalGet('fr/node/' . $node->id());
    $this->assertSession()->pageTextContains('Title in french');
    $this->assertSession()->pageTextNotContains('Title in english (de)');
    // Check editing a translation does not affect the source langcode and
    // check that the paragraphs buttons are still hidden.
    $this->clickLink('Edit');
    $this->assertParagraphsLangcode($node->id(), 'de', 'fr');
    $this->assertNoParagraphsButtons(2);

    // Case 2: original node langcode in DE, change site langcode to DE, change
    // node langcode to EN.

    // Change the site langcode to french.
    $this->drupalGet('admin/config/regional/language');
    $this->submitForm([
      'site_default_language' => 'fr',
    ], 'Save configuration');

    // Check the original node and its paragraphs langcode are still 'de'
    // and the paragraphs buttons are still displayed.
    $this->drupalGet('de/node/' . $node->id() . '/edit');
    $this->assertParagraphsLangcode($node->id(), 'de');
    $this->assertParagraphsButtons(2);

    // Go to the french translation.
    $this->drupalGet('node/' . $node->id() . '/translations');
    $this->clickLink('Edit', 1);
    // Check editing a translation does not affect the source langcode and
    // check that the paragraphs buttons are still hidden.
    $this->assertParagraphsLangcode($node->id(), 'de', 'fr');
    $this->assertNoParagraphsButtons(2);
    // Upload another image.
    $images = $this->getTestFiles('image')[3];
    $this->submitForm([
      'files[field_paragraphs_demo_1_subform_field_paragraphs_demo_0_subform_field_images_demo_1][]' => $images->uri,
    ], 'Upload');
    // Check editing a translation does not affect the source langcode and
    // check that the paragraphs buttons are still hidden.
    $this->assertParagraphsLangcode($node->id(), 'de', 'fr');
    $this->assertNoParagraphsButtons(2);
    $this->submitForm([], 'Save (this translation)');
    // Check the paragraphs langcode are still 'de' after saving the translation.
    $this->assertParagraphsLangcode($node->id(), 'de', 'fr');
    $this->assertSession()->pageTextContains('Title in french');
    $this->assertSession()->pageTextNotContains('Title in english (de)');

    // Back to the original node.
    $this->drupalGet('de/node/' . $node->id());
    $this->assertSession()->pageTextContains('Title in english (de)');
    $this->assertSession()->pageTextNotContains('Title in french');
    // Check the original node and the paragraphs langcode are still 'de' and
    // check that the paragraphs buttons are still displayed.
    $this->clickLink('Edit');
    $this->assertParagraphsLangcode($node->id(), 'de');
    $this->assertParagraphsButtons(2);
    // Change the node langcode back to 'english', add an 'Images' paragraph,
    // check the paragraphs langcode are still 'de' and their buttons are shown.
    $edit = [
      'title[0][value]' => 'Title in english',
      'langcode[0][value]' => 'en',
    ];
    $this->submitForm($edit, 'field_paragraphs_demo_images_add_more');
    $this->assertParagraphsLangcode($node->id(), 'de');
      $this->assertParagraphsButtons(3);
    // Upload a new image, check the paragraphs langcode are still 'de' and the
    // paragraphs buttons are displayed.
    $images = $this->getTestFiles('image')[4];
    $this->submitForm([
      'files[field_paragraphs_demo_2_subform_field_images_demo_0][]' => $images->uri,
    ], 'Upload');
    $this->assertParagraphsLangcode($node->id(), 'de');
    $this->assertParagraphsButtons(3);
    $this->submitForm([], 'Save (this translation)');
    // Check the original node and the paragraphs langcode are now 'en'.
    $this->assertParagraphsLangcode($node->id());
  }

  /**
   * Tests the paragraphs buttons presence in multilingual workflow.
   *
   * This test covers the following test cases:
   * 1) original node langcode in german, change to english.
   * 2) original node langcode in english, change to german.
   * 3) original node langcode in english, change site langcode to german,
   *   change node langcode to german.
   */
  public function testParagraphsMultilingualWorkflow() {
    // Case 1: Check the paragraphs buttons after changing the NODE language
    // (original node langcode in GERMAN, default site langcode in english).
    // Use the stable widget.
    $form_display = EntityFormDisplay::load('node.paragraphed_content_demo.default')
      ->setComponent('field_paragraphs_demo', [
        'type' => 'paragraphs',
      ]);
    $form_display->save();
    // Use the stable widget.
    $form_display = EntityFormDisplay::load('paragraph.nested_paragraph.default')
      ->setComponent('field_paragraphs_demo', [
        'type' => 'paragraphs',
      ]);
    $form_display->save();
    // Create a node and check that the node langcode is 'english'.
    $this->drupalGet('node/add/paragraphed_content_demo');
    $option_field = $this->assertSession()->optionExists('edit-langcode-0-value', 'en');
    $this->assertTrue($option_field->hasAttribute('selected'));
    // Change the node langcode to 'german' and add a 'Nested Paragraph'.
    $edit = [
      'title[0][value]' => 'Title in german',
      'langcode[0][value]' => 'de',
    ];
    $this->submitForm($edit, 'Add nested_paragraph');
    // Check that the paragraphs buttons are displayed and add an 'Images'
    // paragraph inside the nested paragraph.
    $this->assertParagraphsButtons(1);
    $this->submitForm([], 'field_paragraphs_demo_0_subform_field_paragraphs_demo_images_add_more');
    // Upload an image and check the paragraphs buttons are still displayed.
    $images = $this->getTestFiles('image')[0];
    $this->submitForm([
      'files[field_paragraphs_demo_0_subform_field_paragraphs_demo_0_subform_field_images_demo_0][]' => $images->uri,
    ], 'Upload');
    $this->assertParagraphsButtons(1);
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('Title in german');
    $node1 = $this->getNodeByTitle('Title in german');

    // Check the paragraph langcode is 'de' and its buttons are displayed.
    // @todo check for the nested children paragraphs buttons and langcode
    // when it's supported.
    $this->clickLink('Edit');
    $this->assertParagraphsLangcode($node1->id(), 'de');
    $this->assertParagraphsButtons(1);
    // Change the node langcode to 'english' and upload another image.
    $images = $this->getTestFiles('image')[1];
    $edit = [
      'title[0][value]' => 'Title in german (en)',
      'langcode[0][value]' => 'en',
      'files[field_paragraphs_demo_0_subform_field_paragraphs_demo_0_subform_field_images_demo_1][]' => $images->uri,
    ];
    $this->submitForm($edit, 'Upload');
    // Check the paragraph langcode is still 'de' and its buttons are shown.
    $this->assertParagraphsLangcode($node1->id(), 'de');
    $this->assertParagraphsButtons(1);
    $this->submitForm([], 'Save');
    // Check the paragraph langcode is now 'en' after saving.
    $this->assertParagraphsLangcode($node1->id());

    // Check the paragraph langcode is 'en' and its buttons are still shown.
    $this->clickLink('Edit');
    $this->assertParagraphsLangcode($node1->id());
    $this->assertParagraphsButtons(1);

    // Case 2: Check the paragraphs buttons after changing the NODE language
    // (original node langcode in ENGLISH, default site langcode in english).

    // Create another node.
    $this->drupalGet('node/add/paragraphed_content_demo');
    // Check that the node langcode is 'english' and add a 'Nested Paragraph'.
    $option_field = $this->assertSession()->optionExists('edit-langcode-0-value', 'en');
    $this->assertTrue($option_field->hasAttribute('selected'));
    $this->submitForm([], 'Add nested_paragraph');
    // Check that the paragraphs buttons are displayed and add an 'Images'
    // paragraph inside the nested paragraph.
    $this->assertParagraphsButtons(1);
    $this->submitForm([], 'field_paragraphs_demo_0_subform_field_paragraphs_demo_images_add_more');
    // Upload an image and check the paragraphs buttons are still displayed.
    $images = $this->getTestFiles('image')[0];
    $edit = [
      'title[0][value]' => 'Title in english',
      'files[field_paragraphs_demo_0_subform_field_paragraphs_demo_0_subform_field_images_demo_0][]' => $images->uri,
    ];
    $this->submitForm($edit, 'Upload');
    $this->assertParagraphsButtons(1);
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('Title in english');
    $node2 = $this->drupalGetNodeByTitle('Title in english');

    // Check the paragraph langcode is 'en' and its buttons are displayed.
    // @todo check for the nested children paragraphs buttons and langcode
    // when it's supported.
    $this->clickLink('Edit');
    $this->assertParagraphsLangcode($node2->id());
    $this->assertParagraphsButtons(1);
    // Change the node langcode to 'german' and add another 'Images' paragraph.
    $edit = [
      'title[0][value]' => 'Title in english (de)',
      'langcode[0][value]' => 'de',
    ];
    $this->submitForm($edit, 'field_paragraphs_demo_images_add_more');
    // Check the paragraphs langcode are still 'en' and their buttons are shown.
    $this->assertParagraphsLangcode($node2->id());
    $this->assertParagraphsButtons(2);
    // Upload an image, check the paragraphs langcode are still 'en' and their
    // buttons are displayed.
    $images = $this->getTestFiles('image')[1];
    $this->submitForm([
      'files[field_paragraphs_demo_1_subform_field_images_demo_0][]' => $images->uri,
    ], 'Upload');
    $this->assertParagraphsLangcode($node2->id());
    $this->assertParagraphsButtons(2);
    $this->submitForm([], 'Save');
    // Check the paragraphs langcode are now 'de' after saving.
    $this->assertParagraphsLangcode($node2->id(), 'de');

    // Change node langcode back to 'english' and save.
    $this->clickLink('Edit');
    $edit = [
      'title[0][value]' => 'Title in english',
      'langcode[0][value]' => 'en',
    ];
    $this->submitForm($edit, 'Save');
    // Check the paragraphs langcode are now 'en' after saving.
    $this->assertParagraphsLangcode($node2->id());

    // Case 3: Check the paragraphs buttons after changing the SITE language.

    // Change the site langcode to german.
    $edit = [
      'site_default_language' => 'de',
    ];
    $this->drupalGet('admin/config/regional/language');
    $this->submitForm($edit, 'Save configuration');

    // Check the original node and the paragraphs langcode are still 'en' and
    // check that the paragraphs buttons are still displayed.
    $this->drupalGet('node/' . $node2->id() . '/edit');
    $this->assertParagraphsLangcode($node2->id());
    $this->assertParagraphsButtons(2);
    // Add another 'Images' paragraph with node langcode as 'english'.
    $this->submitForm([], 'field_paragraphs_demo_images_add_more');
    // Check the paragraph langcode are still 'en' and their buttons are shown.
    $this->assertParagraphsLangcode($node2->id());
    $this->assertParagraphsButtons(3);
    // Upload an image, check the paragraphs langcode are still 'en' and their
    // buttons are displayed.
    $images = $this->getTestFiles('image')[2];
    $this->submitForm([
      'files[field_paragraphs_demo_2_subform_field_images_demo_0][]' => $images->uri,
    ], 'Upload');
    $this->assertParagraphsLangcode($node2->id());
    $this->assertParagraphsButtons(3);
    $this->submitForm([], 'Save');
    // Check the paragraphs langcode are still 'en' after saving.
    $this->assertParagraphsLangcode($node2->id());

    // Check the paragraphs langcode are still 'en' and their buttons are shown.
    $this->clickLink('Edit');
    $this->assertParagraphsLangcode($node2->id());
    $this->assertParagraphsButtons(3);
    // Change node langcode to 'german' and add another 'Images' paragraph.
    $edit = [
      'title[0][value]' => 'Title in english (de)',
      'langcode[0][value]' => 'de',
    ];
    $this->submitForm($edit, 'field_paragraphs_demo_images_add_more');
    // Check the paragraphs langcode are still 'en' and their buttons are shown.
    $this->assertParagraphsLangcode($node2->id());
    $this->assertParagraphsButtons(4);
    // Upload an image, check the paragraphs langcode are still 'en' and their
    // buttons are displayed.
    $images = $this->getTestFiles('image')[3];
    $this->submitForm([
      'files[field_paragraphs_demo_3_subform_field_images_demo_0][]' => $images->uri,
    ], 'Upload');
    $this->assertParagraphsLangcode($node2->id());
    $this->assertParagraphsButtons(4);
    $this->submitForm([], 'Save');
    // Check the paragraphs langcode are now 'de' after saving.
    $this->assertParagraphsLangcode($node2->id(), 'de');
  }

  /**
   * Passes if the paragraphs buttons are present.
   *
   * @param int $count
   *   Number of paragraphs buttons to look for.
   */
  protected function assertParagraphsButtons($count) {
    $this->assertParagraphsButtonsHelper($count, FALSE);
  }

  /**
   * Passes if the paragraphs buttons are NOT present.
   *
   * @param int $count
   *   Number of paragraphs buttons to look for.
   */
  protected function assertNoParagraphsButtons($count) {
    $this->assertParagraphsButtonsHelper($count, TRUE);
  }

  /**
   * Helper for assertParagraphsButtons and assertNoParagraphsButtons.
   *
   * @param int $count
   *   Number of paragraphs buttons to look for.
   * @param bool $hidden
   *   TRUE if these buttons should not be shown, FALSE otherwise.
   *   Defaults to TRUE.
   */
  protected function assertParagraphsButtonsHelper($count, $hidden = TRUE) {
    for ($i = 0; $i < $count; $i++) {
      $remove_button = $this->xpath('//*[@name="field_paragraphs_demo_' . $i . '_remove"]');
      if (!$hidden) {
        $this->assertNotEquals(count($remove_button), 0);
      }
      else {
        $this->assertEquals(count($remove_button), 0);
      }
    }

    // It is enough to check for the specific paragraph type 'Images' to assert
    // the add more buttons presence for this test class.
    $add_button = $this->xpath('//input[@value="Add images"]');
    if (!$hidden) {
      $this->assertNotEquals(count($add_button), 0);
    }
    else {
      $this->assertEquals(count($add_button), 0);
    }
  }

  /**
   * Assert each paragraph items have the same langcode as the node one.
   *
   * @param string $node_id
   *   The node ID which contains the paragraph items to be checked.
   * @param string $source_lang
   *   The expected node source langcode. Defaults to 'en'.
   * @param string $trans_lang
   *   The expected translated node langcode. Defaults to NULL.
   */
  protected function assertParagraphsLangcode($node_id, $source_lang = 'en', $trans_lang = NULL) {
    // Update the outdated node and check all the paragraph items langcodes.
    \Drupal::entityTypeManager()->getStorage('node')->resetCache([$node_id]);
    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::load($node_id);
    $node_langcode = $node->langcode->value;
    $this->assertEquals($node_langcode, $source_lang, 'Host langcode matches.');

    /** @var \Drupal\Core\Entity\ContentEntityBase $paragraph */
    foreach ($node->field_paragraphs_demo->referencedEntities() as $paragraph) {
      $paragraph_langcode = $paragraph->language()->getId();
      $message = new FormattableMarkup('Node langcode is "@node", paragraph item langcode is "@item".', ['@node' => $source_lang, '@item' => $paragraph_langcode]);
      $this->assertEquals($paragraph_langcode, $source_lang, $message);
    }

    // Check the translation.
    if (!empty($trans_lang)) {
      $this->assertTrue($node->hasTranslation($trans_lang), 'Translation exists.');
    }
    if ($node->hasTranslation($trans_lang)) {
      $trans_node = $node->getTranslation($trans_lang);
      $trans_node_langcode = $trans_node->language()->getId();
      $this->assertEquals($trans_node_langcode, $trans_lang, 'Translated node langcode matches.');

      // Check the paragraph item langcode matching the translated node langcode.
      foreach ($trans_node->field_paragraphs_demo->referencedEntities() as $paragraph) {
        if ($paragraph->hasTranslation($trans_lang)) {
          $trans_item = $paragraph->getTranslation($trans_lang);
          $paragraph_langcode = $trans_item->language()->getId();
          $message = new FormattableMarkup('Translated node langcode is "@node", paragraph item langcode is "@item".', ['@node' => $trans_lang, '@item' => $paragraph_langcode]);
          $this->assertEquals($paragraph_langcode, $trans_lang, $message);
        }
      }
    }
  }
}
