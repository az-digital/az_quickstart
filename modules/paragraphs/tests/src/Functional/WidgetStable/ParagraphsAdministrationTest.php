<?php

namespace Drupal\Tests\paragraphs\Functional\WidgetStable;

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;

/**
 * Tests the configuration of paragraphs.
 *
 * @group paragraphs
 */
class ParagraphsAdministrationTest extends ParagraphsTestBase {

  use ParagraphsTestBaseTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = array(
    'image',
    'file',
    'views'
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create paragraphs content type.
    $this->drupalCreateContentType(array('type' => 'paragraphs', 'name' => 'Paragraphs'));
  }

  /**
   * Tests the revision of paragraphs.
   */
  public function testParagraphsRevisions() {
    $this->addParagraphedContentType('article', 'paragraphs');
    $this->loginAsAdmin([
      'create paragraphs content',
      'administer node display',
      'edit any paragraphs content',
      'administer nodes',
    ]);

    // Create paragraphs type Headline + Block.
    $this->addParagraphsType('text');
    // Create field types for the text.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/text', 'text', 'Text', 'text', array(), array());
    $this->assertSession()->pageTextContains('Saved Text configuration.');

    // Create an article with paragraphs field.
    static::fieldUIAddNewField('admin/structure/types/manage/paragraphs', 'paragraphs', 'Paragraphs', 'entity_reference_revisions', array(
      'settings[target_type]' => 'paragraph',
      'cardinality' => '-1',
    ), array(
      'settings[handler_settings][target_bundles_drag_drop][text][enabled]' => TRUE,
    ));
    // Configure article fields.
    $this->drupalGet('admin/structure/types/manage/paragraphs/fields');
    $this->clickLink('Manage form display');
    $this->submitForm(array('fields[field_paragraphs][type]' => 'paragraphs'), 'Save');

    // Create node with our paragraphs.
    $this->drupalGet('node/add/paragraphs');
    $this->submitForm(array(), 'field_paragraphs_text_add_more');
    $this->submitForm(array(), 'field_paragraphs_text_add_more');
    $edit = [
      'title[0][value]' => 'TEST TITEL',
      'field_paragraphs[0][subform][field_text][0][value]' => 'Test text 1',
      'field_paragraphs[1][subform][field_text][0][value]' => 'Test text 2',
    ];
    $this->submitForm($edit + ['status[value]' => TRUE], 'Save');

    $node = $this->drupalGetNodeByTitle('TEST TITEL');
    $paragraph1 = $node->field_paragraphs[0]->target_id;
    $paragraph2 = $node->field_paragraphs[1]->target_id;

    $this->countRevisions($node, $paragraph1, $paragraph2, 1);

    // Edit the node without creating a revision. There should still be only 1
    // revision for nodes and paragraphs.
    $edit = [
      'field_paragraphs[0][subform][field_text][0][value]' => 'Foo Bar 1',
      'revision' => FALSE,
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');

    $this->countRevisions($node, $paragraph1, $paragraph2, 1);

    // Edit the just created node. Create new revision. Now we should have 2
    // revisions for nodes and paragraphs.
    $edit = [
      'title[0][value]' => 'TEST TITLE',
      'field_paragraphs[0][subform][field_text][0][value]' => 'Foo Bar 2',
      'revision' => TRUE,
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');

    $this->countRevisions($node, $paragraph1, $paragraph2, 2);

    // Assert the paragraphs have been changed.
    $this->assertSession()->pageTextNotContains('Foo Bar 1');
    $this->assertSession()->pageTextContains('Test text 2');
    $this->assertSession()->pageTextContains('Foo Bar 2');
    $this->assertSession()->pageTextContains('TEST TITLE');

    // Check out the revisions page and assert there are 2 revisions.
    $this->drupalGet('node/' . $node->id() . '/revisions');
    $rows = $this->xpath('//tbody/tr');
    // Make sure two revisions available.
    $this->assertEquals(count($rows), 2);
    // Revert to the old version.
    $this->clickLink('Revert');
    $this->submitForm([], 'Revert');
    $this->drupalGet('node/' . $node->id());
    // Assert the node has been reverted.
    $this->assertSession()->pageTextNotContains('Foo Bar 2');
    $this->assertSession()->pageTextContains('Test text 2');
    $this->assertSession()->pageTextContains('Foo Bar 1');
    $this->assertSession()->pageTextContains('TEST TITEL');
  }


  /**
   * Tests the paragraph creation.
   */
  public function testParagraphsCreation() {
    // Create an article with paragraphs field.
    $this->addParagraphedContentType('article');
    $this->loginAsAdmin([
      'administer site configuration',
      'create article content',
      'create paragraphs content',
      'administer node display',
      'administer paragraph display',
      'edit any article content',
      'delete any article content',
      'access files overview',
    ]);

    // Assert suggested 'Add a paragraph type' link when there is no type yet.
    $this->drupalGet('admin/structure/paragraphs_type');
    $this->assertSession()->pageTextContains('There are no Paragraphs types yet.');
    $this->drupalGet('admin/structure/types/manage/paragraphs/fields/add-field');
    $this->getSession()->getPage()->fillField('new_storage_type', 'field_ui:entity_reference_revisions:paragraph');
    if ($this->coreVersion('10.3')) {
      $this->getSession()->getPage()->pressButton('Continue');
    }
    $edit = [
      'label' => 'Paragraph',
      'field_name' => 'paragraph',
    ];
    $this->submitForm($edit, 'Continue');

    $this->assertSession()->linkByHrefExists('admin/structure/paragraphs_type/add');
    $this->clickLink('here');
    $this->assertSession()->addressEquals('admin/structure/paragraphs_type/add');

    $this->drupalGet('admin/structure/paragraphs_type');
    $this->clickLink('Add paragraph type');
    $this->assertSession()->titleEquals('Add Paragraphs type | Drupal');
    // Create paragraph type text + image.
    $this->addParagraphsType('text_image');
    $this->drupalGet('admin/structure/paragraphs_type/text_image');
    $this->assertSession()->titleEquals('Edit text_image paragraph type | Drupal');
    // Create field types for text and image.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/text_image', 'text', 'Text', 'text_long', array(), array());
    $this->assertSession()->pageTextContains('Saved Text configuration.');
    static::fieldUIAddNewField('admin/structure/paragraphs_type/text_image', 'image', 'Image', 'image', array(), array('settings[alt_field_required]' => FALSE));
    $this->assertSession()->pageTextContains('Saved Image configuration.');

    // Create paragraph type Nested test.
    $this->addParagraphsType('nested_test');

    static::fieldUIAddNewField('admin/structure/paragraphs_type/nested_test', 'paragraphs', 'Paragraphs', 'entity_reference_revisions', array(
      'settings[target_type]' => 'paragraph',
      'cardinality' => '-1',
    ), array());

    // Change the add more button to select mode.
    $this->clickLink('Manage form display');
    $this->submitForm(['fields[field_paragraphs][type]' => 'paragraphs'], 'field_paragraphs_settings_edit');
    $this->submitForm(['fields[field_paragraphs][settings_edit_form][settings][add_mode]' => 'select'], 'Update');
    $this->submitForm([], 'Save');

    // Create paragraph type image.
    $this->addParagraphsType('image');
    // Create field types for image.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/image', 'image_only', 'Image only', 'image', array(), array());
    $this->assertSession()->pageTextContains('Saved Image only configuration.');

    $this->drupalGet('admin/structure/paragraphs_type');
    $rows = $this->xpath('//tbody/tr');
    // Make sure 2 types are available with their label.
    $this->assertEquals(count($rows), 3);
    $this->assertSession()->pageTextContains('text_image');
    $this->assertSession()->pageTextContains('image');
    // Make sure there is an edit link for each type.
    $this->clickLink('Edit');
    // Make sure the field UI appears.
    $this->assertSession()->linkExists('Manage fields');
    $this->assertSession()->linkExists('Manage form display');
    $this->assertSession()->linkExists('Manage display');
    $this->assertSession()->titleEquals('Edit image paragraph type | Drupal');

    // Test for "Add mode" setting.
    $this->drupalGet('admin/structure/types/manage/article/form-display');
    $field_name = 'field_paragraphs';

    // Click on the widget settings button to open the widget settings form.
    $this->submitForm(['fields[field_paragraphs][type]' => 'paragraphs'], $field_name . "_settings_edit");

    // Enable setting.
    $edit = array('fields[' . $field_name . '][settings_edit_form][settings][add_mode]' => 'button');
    $this->submitForm($edit, 'Save');

    // Check if the setting is stored.
    $this->drupalGet('admin/structure/types/manage/article/form-display');
    $this->assertSession()->pageTextContains('Add mode: Buttons');

    $this->submitForm(array(), $field_name . "_settings_edit");
    // Assert the 'Buttons' option is selected.
    $add_mode_option = $this->assertSession()->optionExists('edit-fields-field-paragraphs-settings-edit-form-settings-add-mode', 'button');
    $this->assertTrue($add_mode_option->hasAttribute('selected'), 'Updated value is correct!.');

    // Add two Text + Image paragraphs in article.
    $this->drupalGet('node/add/article');
    $this->submitForm(array(), 'field_paragraphs_text_image_add_more');
    $this->submitForm(array(), 'field_paragraphs_text_image_add_more');

    // Upload some images.
    $files = $this->getTestFiles('image');
    $file_system = \Drupal::service('file_system');

    $edit = array(
      'title[0][value]' => 'Test article',
      'field_paragraphs[0][subform][field_text][0][value]' => 'Test text 1',
      'files[field_paragraphs_0_subform_field_image_0]' => $file_system->realpath($files[0]->uri),
      'field_paragraphs[1][subform][field_text][0][value]' => 'Test text 2',
      'files[field_paragraphs_1_subform_field_image_0]' => $file_system->realpath($files[1]->uri),
    );
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('article Test article has been created.');

    $node = $this->drupalGetNodeByTitle('Test article');
    $img1_url = \Drupal::service('file_url_generator')->generateString(\Drupal::token()->replace('public://[date:custom:Y]-[date:custom:m]/' . $files[0]->filename));
    $img2_url = \Drupal::service('file_url_generator')->generateString(\Drupal::token()->replace('public://[date:custom:Y]-[date:custom:m]/' . $files[1]->filename));
    $img1_mime = \Drupal::service('file.mime_type.guesser')->guessMimeType($files[0]->uri);
    $img2_mime = \Drupal::service('file.mime_type.guesser')->guessMimeType($files[1]->uri);

    // Check the text and image after publish.
    $this->assertSession()->pageTextContains('Test text 1');
    $this->assertSession()->elementExists('css', 'img[src="' . $img1_url . '"]');
    $this->assertSession()->pageTextContains('Test text 2');
    $this->assertSession()->elementExists('css', 'img[src="' . $img2_url . '"]');

    // Tests for "Edit mode" settings.
    // Test for closed setting.
    $this->drupalGet('admin/structure/types/manage/article/form-display');
    // Click on the widget settings button to open the widget settings form.
    $this->submitForm(array(), "field_paragraphs_settings_edit");
    // Enable setting.
    $edit = array('fields[field_paragraphs][settings_edit_form][settings][edit_mode]' => 'closed');
    $this->submitForm($edit, 'Save');
    // Check if the setting is stored.
    $this->assertSession()->pageTextContains('Edit mode: Closed');
    $this->submitForm(array(), "field_paragraphs_settings_edit");
    // Assert the 'Closed' option is selected.
    $edit_mode_option = $this->assertSession()->optionExists('edit-fields-field-paragraphs-settings-edit-form-settings-edit-mode', 'closed');
    $this->assertTrue($edit_mode_option->hasAttribute('selected'), 'Updated value correctly.');
    $this->drupalGet('node/1/edit');
    // The textareas for paragraphs should not be visible.
    $this->assertSession()->responseNotContains('field_paragraphs[0][subform][field_text][0][value]');
    $this->assertSession()->responseNotContains('field_paragraphs[1][subform][field_text][0][value]');
    $this->assertSession()->responseContains('<span class="summary-content">Test text 1</span>, <span class="summary-content">' . $files[0]->filename);
    $this->assertSession()->responseContains('<span class="summary-content">Test text 2</span>, <span class="summary-content">' . $files[1]->filename);

    // Test for preview option.
    $this->drupalGet('admin/structure/types/manage/article/form-display');
    $this->submitForm(array(), "field_paragraphs_settings_edit");
    $edit = [
      'fields[field_paragraphs][settings_edit_form][settings][edit_mode]' => 'closed',
      'fields[field_paragraphs][settings_edit_form][settings][closed_mode]' => 'preview',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Edit mode: Closed');
    $this->assertSession()->pageTextContains('Closed mode: Preview');
    $this->drupalGet('node/1/edit');
    // The texts in the paragraphs should be visible.
    $this->assertSession()->responseNotContains('field_paragraphs[0][subform][field_text][0][value]');
    $this->assertSession()->responseNotContains('field_paragraphs[1][subform][field_text][0][value]');
    $this->assertSession()->pageTextContains('Test text 1');
    $this->assertSession()->pageTextContains('Test text 2');

    // Test for open option.
    $this->drupalGet('admin/structure/types/manage/article/form-display');
    $this->submitForm(array(), "field_paragraphs_settings_edit");
    // Assert the "Closed" and "Preview" options are selected.
    $edit_mode_option = $this->assertSession()->optionExists('edit-fields-field-paragraphs-settings-edit-form-settings-edit-mode', 'closed');
    $this->assertTrue($edit_mode_option->hasAttribute('selected'), 'Correctly updated the "Edit mode" value.');
    $closed_mode_option = $this->assertSession()->optionExists('edit-fields-field-paragraphs-settings-edit-form-settings-closed-mode', 'preview');
    $this->assertTrue($closed_mode_option->hasAttribute('selected'),'Correctly updated the "Closed mode" value.');
    // Restore the value to Open for next test.
    $edit = array('fields[field_paragraphs][settings_edit_form][settings][edit_mode]' => 'open');
    $this->submitForm($edit, 'Save');
    $this->drupalGet('node/1/edit');
    // The textareas for paragraphs should be visible.
    $this->assertSession()->responseContains('field_paragraphs[0][subform][field_text][0][value]');
    $this->assertSession()->responseContains('field_paragraphs[1][subform][field_text][0][value]');

    $paragraphs = Paragraph::loadMultiple();
    $this->assertEquals(count($paragraphs), 2, 'Two paragraphs in article');

    // Check article edit page.
    $this->drupalGet('node/' . $node->id() . '/edit');
    // Check both paragraphs in edit page.
    $this->assertSession()->fieldValueEquals('field_paragraphs[0][subform][field_text][0][value]', 'Test text 1');
    $this->assertSession()->elementTextContains('css', 'A[href="' . $img1_url . '"][type^="' . $img1_mime . '"]', $files[0]->filename);
    $this->assertSession()->fieldValueEquals('field_paragraphs[1][subform][field_text][0][value]', 'Test text 2');
    $this->assertSession()->elementTextContains('css', 'A[href="' . $img2_url . '"][type^="' . $img2_mime . '"]', $files[1]->filename);
    // Remove 2nd paragraph.
    $this->getSession()->getPage()->find('css', '[name="field_paragraphs_1_remove"]')->press();
    $this->assertSession()->fieldNotExists('field_paragraphs[1][subform][field_text][0][value]');
    $this->assertSession()->elementNotExists('css', 'A[href="' . $img2_url . '"][type^="' . $img2_mime . '"]');
    // Assert the paragraph is not deleted unless the user saves the node.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->elementTextContains('css', 'A[href="' . $img2_url . '"][type^="' . $img2_mime . '"]', $files[1]->filename);
    // Remove the second paragraph.
    $this->getSession()->getPage()->find('css', '[name="field_paragraphs_1_remove"]')->press();
    $this->assertSession()->elementNotExists('css', 'A[href="' . $img2_url . '"][type^="' . $img2_mime . '"]');
    $edit = [
      'field_paragraphs[0][subform][field_image][0][alt]' => 'test_alt',
    ];
    $this->submitForm($edit, 'Save');
    // Assert the paragraph is deleted after the user saves the node.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->elementNotExists('css', 'A[href="' . $img2_url . '"][type^="' . $img2_mime . '"]');

    // Delete the node.
    $this->clickLink('Delete');
    $this->submitForm([], 'Delete');
    $this->assertSession()->pageTextContains('Test article has been deleted.');

    // Check if the publish/unpublish option works.
    $this->drupalGet('admin/structure/paragraphs_type/text_image/form-display');
    $edit = [
      'fields[status][type]' => 'boolean_checkbox',
      'fields[status][region]' => 'content',
    ];

    $this->submitForm($edit, 'Save');
    $this->drupalGet('node/add/article');
    $this->submitForm([], 'Add text_image');
    $this->assertSession()->responseContains('edit-field-paragraphs-0-subform-status-value');
    $edit = [
      'title[0][value]' => 'Example publish/unpublish',
      'field_paragraphs[0][subform][field_text][0][value]' => 'Example published and unpublished',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Example published and unpublished');
    $this->clickLink('Edit');
    $edit = [
      'field_paragraphs[0][subform][status][value]' => FALSE,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextNotContains('Example published and unpublished');

    // Set the fields as required.
    $this->drupalGet('admin/structure/types/manage/article/fields');
    $this->clickLink('Edit', 1);
    $this->submitForm(['preview_mode' => '1'], 'Save');
    $this->drupalGet('admin/structure/paragraphs_type/nested_test/fields');
    $this->clickLink('Edit');
    $this->submitForm(['required' => TRUE], 'Save settings');

    // Add a new article.
    $this->drupalGet('node/add/article');
    $this->submitForm([], 'field_paragraphs_nested_test_add_more');

    // Ensure that nested header actions do not add a visible weight field.
    $this->assertSession()->fieldNotExists('field_paragraphs[0][subform][field_paragraphs][header_actions][_weight]');

    $edit = [
      'field_paragraphs[0][subform][field_paragraphs][add_more][add_more_select]' => 'image',
    ];
    $this->submitForm($edit, 'field_paragraphs_0_subform_field_paragraphs_add_more');
    // Test the new field is displayed.
    $this->assertSession()->fieldExists('files[field_paragraphs_0_subform_field_paragraphs_0_subform_field_image_only_0]');

    // Add an image to the required field.
    $edit = array(
      'title[0][value]' => 'test required',
      'files[field_paragraphs_0_subform_field_paragraphs_0_subform_field_image_only_0]' => $file_system->realpath($files[2]->uri),
    );
    $this->submitForm($edit, 'Save');
    $edit = [
      'field_paragraphs[0][subform][field_paragraphs][0][subform][field_image_only][0][alt]' => 'Alternative_text',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('test required has been created.');
    $this->assertSession()->responseNotContains('This value should not be null.');

    // Test that unsupported widgets are not displayed.
    $this->drupalGet('admin/structure/types/manage/article/form-display');
    $select = $this->xpath('//*[@id="edit-fields-field-paragraphs-type"]')[0];
    $this->assertCount(2, $select->findAll('css', 'option'));
    $this->assertSession()->responseContains('value="paragraphs" selected="selected"');

    // Test that all Paragraph types can be referenced if none is selected.
    $this->addParagraphsType('nested_double_test');
    static::fieldUIAddExistingField('admin/structure/paragraphs_type/nested_double_test', 'field_paragraphs', 'paragraphs_1');
    $this->clickLink('Manage form display');
    // Fields now keep form display settings when reused in 10.1+, restore it to the
    // default.
    $this->submitForm(['fields[field_paragraphs][type]' => 'paragraphs'], 'field_paragraphs_settings_edit');
    $this->submitForm(['fields[field_paragraphs][settings_edit_form][settings][add_mode]' => 'dropdown'], 'Update');
    $this->submitForm([], 'Save');
    //$this->drupalPostForm(NULL, array('fields[field_paragraphs][type]' => 'entity_reference_revisions_entity_view'), 'Save');
    static::fieldUIAddNewField('admin/structure/paragraphs_type/nested_double_test', 'paragraphs_2', 'paragraphs_2', 'entity_reference_revisions', array(
      'settings[target_type]' => 'paragraph',
      'cardinality' => '-1',
    ), array());
    $this->clickLink('Manage form display');
    $this->submitForm([], 'Save');
    $this->drupalGet('node/add/article');
    $this->submitForm([], 'field_paragraphs_nested_test_add_more');
    $edit = [
      'field_paragraphs[0][subform][field_paragraphs][add_more][add_more_select]' => 'nested_double_test',
    ];
    $this->submitForm($edit, 'field_paragraphs_0_subform_field_paragraphs_add_more');
    $this->submitForm([], 'field_paragraphs_0_subform_field_paragraphs_0_subform_field_paragraphs_image_add_more');
    $edit = array(
      'title[0][value]' => 'Nested twins',
    );
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Nested twins has been created.');
    $this->assertSession()->pageTextNotContains('This entity (paragraph: ) cannot be referenced.');

    // Set the fields as not required.
    $this->drupalGet('admin/structure/types/manage/article/fields/node.article.field_paragraphs');
    $this->submitForm(['required' => FALSE], 'Save');

    // Set the Paragraph field edit mode to "Closed" and the closed mode to
    // "Summary".
    $settings = [
      'edit_mode' => 'closed',
      'closed_mode' => 'summary',
    ];
    $this->setParagraphsWidgetSettings('article', 'field_paragraphs', $settings);

    $this->addParagraphsType('node_test');

    // Add a required node reference field.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/node_test', 'entity_reference', 'Entity reference', 'entity_reference', array(
      'settings[target_type]' => 'node',
      'cardinality' => '-1'
    ), [
      'settings[handler_settings][target_bundles][article]' => TRUE,
      'required' => TRUE,
    ]);
    $node = $this->drupalGetNodeByTitle('Nested twins');

    // Create a node with a reference in a Paragraph.
    $this->drupalGet('node/add/article');
    $this->submitForm([], 'field_paragraphs_node_test_add_more');
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    $edit = [
      'field_paragraphs[0][subform][field_entity_reference][0][target_id]' => $node->label() . ' (' . $node->id() . ')',
      'title[0][value]' => 'choke test',
    ];
    $this->submitForm($edit, 'Save');
    // Delete the referenced node.
    $node->delete();
    // Edit the node with the reference.
    $this->clickLink('Edit');

    // Adding another required paragraph and deleting that again should not
    // validate closed paragraphs but trying to save the node should.
    $this->submitForm(array(), 'field_paragraphs_node_test_add_more');
    $this->assertSession()->pageTextNotContains('The referenced entity (node: ' . $node->id() . ') does not exist.');
    $this->assertSession()->fieldExists('field_paragraphs[1][subform][field_entity_reference][0][target_id]');
    $this->submitForm(array(), 'field_paragraphs_1_remove');
    $this->assertSession()->pageTextNotContains('The referenced entity (node: ' . $node->id() . ') does not exist.');
    $this->assertSession()->fieldNotExists('field_paragraphs[1][subform][field_entity_reference][0][target_id]');
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('Error in field field_paragraphs #1 (node_test), Entity reference : The referenced entity (node: ' . $node->id() . ') does not exist.');

    // Attempt to edit the Paragraph.
    $this->submitForm([], 'field_paragraphs_0_edit');
    // Try to collapse with an invalid reference.
    $this->submitForm(['field_paragraphs[0][subform][field_entity_reference][0][target_id]' => 'foo'], 'field_paragraphs_0_collapse');
    // Paragraph should be still in edit mode.
    $this->assertSession()->fieldExists('field_paragraphs[0][subform][field_entity_reference][0][target_id]');
    $this->assertSession()->fieldExists('field_paragraphs[0][subform][field_entity_reference][1][target_id]');
    // Assert the validation message.
    $this->assertSession()->pageTextMatches('/There are no (entities|content items) matching "foo"./');
    // Fix the broken reference.
    $node = $this->drupalGetNodeByTitle('Example publish/unpublish');
    $edit = ['field_paragraphs[0][subform][field_entity_reference][0][target_id]' => $node->label() . ' (' . $node->id() . ')'];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('choke test has been updated.');
    $this->assertSession()->linkExists('Example publish/unpublish');
    // Delete the new referenced node.
    $node->delete();

    // Set the Paragraph field closed mode to "Preview".
    $settings = [
      'edit_mode' => 'closed',
      'closed_mode' => 'preview',
    ];
    $this->setParagraphsWidgetSettings('article', 'field_paragraphs', $settings);

    $node = $this->drupalGetNodeByTitle('choke test');
    // Attempt to edit the Paragraph.
    $this->drupalGet('node/' . $node->id() . '/edit');
    // Attempt to edit the Paragraph.
    $this->submitForm([], 'field_paragraphs_0_edit');
    // Try to save with an invalid reference.
    $edit = ['field_paragraphs[0][subform][field_entity_reference][0][target_id]' => 'foo'];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextMatches('/There are no (entities|content items) matching "foo"./');
    // Remove the Paragraph and save the node.
    $this->submitForm([], 'field_paragraphs_0_remove');
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('choke test has been updated.');

    $this->drupalGet('admin/structure/types/manage/article/fields');
    $this->clickLink('Edit');
    $this->submitForm(['description' => 'This is the description of the field.'], 'Save settings');
    // Verify that the text displayed is correct when no paragraph has been
    // added yet.
    $this->drupalGet('node/add/article');
    $this->assertSession()->pageTextContains('This is the description of the field.');
    $elements = $this->xpath('//table[@id="field-paragraphs-values"]/tbody');
    $header = $this->xpath('//table[@id="field-paragraphs-values"]/thead');
    $this->assertEquals($elements, []);
    $this->assertNotEquals($header, []);

    $this->drupalGet('admin/content/files');
    $this->clickLink('1 place');
    $label = $this->xpath('//tbody/tr/td[1]');
    $this->assertEquals(trim(htmlspecialchars_decode(strip_tags($label[0]->getText()))), 'test required > field_paragraphs > Paragraphs');
  }

  /**
   * Helper function for revision counting.
   */
  private function countRevisions($node, $paragraph1, $paragraph2, $revisions_count) {
    $node_revisions_count = \Drupal::entityQuery('node')
      ->condition('nid', $node->id())
      ->accessCheck(TRUE)
      ->allRevisions()
      ->count()
      ->execute();
    $this->assertEquals($revisions_count, $node_revisions_count);
    $paragraph1_revisions_count = \Drupal::entityQuery('paragraph')
      ->condition('id', $paragraph1)
      ->accessCheck(TRUE)
      ->allRevisions()
      ->count()
      ->execute();
    $this->assertEquals($revisions_count, $paragraph1_revisions_count);
    $paragraph2_revisions_count =\Drupal::entityQuery('paragraph')
      ->condition('id', $paragraph2)
      ->accessCheck(TRUE)
      ->allRevisions()
      ->count()
      ->execute();
    $this->assertEquals($revisions_count, $paragraph2_revisions_count);
  }

}
