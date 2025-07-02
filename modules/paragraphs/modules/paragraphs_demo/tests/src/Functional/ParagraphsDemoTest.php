<?php

namespace Drupal\Tests\paragraphs_demo\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\paragraphs\Traits\ParagraphsCoreVersionUiTestTrait;

/**
 * Tests the demo module for Paragraphs.
 *
 * @group paragraphs
 */
class ParagraphsDemoTest extends BrowserTestBase {

  use ParagraphsCoreVersionUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  protected static $modules = array(
    'paragraphs_demo',
    'block',
  );

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
   * Asserts demo paragraphs have been created.
   */
  public function testConfigurationsAndCreation() {

    // Assert that the demo page is displayed to anymous users.
    $this->drupalGet('');
    $this->assertSession()->pageTextContains('Paragraphs is the new way of content creation!');
    $this->assertSession()->pageTextContains('Apart from the included Paragraph types');
    $this->assertSession()->pageTextContains('A search api example can be found');
    $this->assertSession()->pageTextContains('This is content from the library. We can reuse it multiple times without duplicating it.');

    $admin_user = $this->drupalCreateUser(array(
      'administer site configuration',
      'create paragraphed_content_demo content',
      'edit any paragraphed_content_demo content',
      'delete any paragraphed_content_demo content',
      'administer content translation',
      'create content translations',
      'bypass node access',
      'use editorial transition create_new_draft',
      'use editorial transition publish',
      'use editorial transition archived_published',
      'use editorial transition archived_draft',
      'use editorial transition archive',
      'administer languages',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer paragraphs types',
      'administer paragraph fields',
      'administer paragraph display',
      'administer paragraph form display',
      'administer node form display',
      'administer paragraphs library',
      'use text format basic_html',
    ));

    $this->drupalLogin($admin_user);

    // Set edit mode to open.
    $this->drupalGet('admin/structure/types/manage/paragraphed_content_demo/form-display');
    $this->submitForm([], "field_paragraphs_demo_settings_edit");
    $edit = ['fields[field_paragraphs_demo][settings_edit_form][settings][edit_mode]' => 'open'];
    $this->submitForm($edit, 'Save');

    // Check for all pre-configured paragraphs_types.
    $this->drupalGet('admin/structure/paragraphs_type');
    $this->assertSession()->pageTextContains('Image + Text');
    $this->assertSession()->pageTextContains('Images');
    $this->assertSession()->pageTextContains('Text');
    $this->assertSession()->pageTextContains('Text + Image');
    $this->assertSession()->pageTextContains('User');

    // Check for preconfigured languages.
    $this->drupalGet('admin/config/regional/language');
    $this->assertSession()->pageTextContains('English');
    $this->assertSession()->pageTextContains('German');
    $this->assertSession()->pageTextContains('French');

    // Check for Content language translation checks.
    $this->drupalGet('admin/config/regional/content-language');
    $this->assertSession()->checkboxChecked('edit-entity-types-node');
    $this->assertSession()->checkboxChecked('edit-entity-types-paragraph');
    $this->assertSession()->checkboxChecked('edit-settings-node-paragraphed-content-demo-translatable');
    $this->assertSession()->checkboxNotChecked('edit-settings-node-paragraphed-content-demo-fields-field-paragraphs-demo');
    $this->assertSession()->checkboxChecked('edit-settings-paragraph-images-translatable');
    $this->assertSession()->checkboxChecked('edit-settings-paragraph-image-text-translatable');
    $this->assertSession()->checkboxChecked('edit-settings-paragraph-text-translatable');
    $this->assertSession()->checkboxChecked('edit-settings-paragraph-text-image-translatable');
    $this->assertSession()->checkboxChecked('edit-settings-paragraph-user-translatable');

    // Check for paragraph type Image + text that has the correct fields set.
    $this->drupalGet('admin/structure/paragraphs_type/image_text/fields');
    $this->assertSession()->pageTextContains('Text');
    $this->assertSession()->pageTextContains('Image');

    // Check for paragraph type Text that has the correct fields set.
    $this->drupalGet('admin/structure/paragraphs_type/text/fields');
    $this->assertSession()->pageTextContains('Text');
    $this->assertSession()->pageTextNotContains('Image');

    // Make sure we have the paragraphed article listed as a content type.
    $this->drupalGet('admin/structure/types');
    $this->assertSession()->pageTextContains('Paragraphed article');

    // Check that title and the descriptions are set.
    $this->drupalGet('admin/structure/types/manage/paragraphed_content_demo');
    $this->assertSession()->pageTextContains('Paragraphed article');
    $this->assertSession()->pageTextContains('Article with Paragraphs.');

    // Check that the Paragraph field is added.
    $this->clickLink('Manage fields');
    $this->assertSession()->pageTextContains('Paragraphs');

    // Check that all paragraphs types are enabled (disabled).
    $this->clickLink('Edit', 0);
    $this->assertSession()->checkboxNotChecked('edit-settings-handler-settings-target-bundles-drag-drop-image-text-enabled');
    $this->assertSession()->checkboxNotChecked('edit-settings-handler-settings-target-bundles-drag-drop-images-enabled');
    $this->assertSession()->checkboxNotChecked('edit-settings-handler-settings-target-bundles-drag-drop-text-image-enabled');
    $this->assertSession()->checkboxNotChecked('edit-settings-handler-settings-target-bundles-drag-drop-user-enabled');
    $this->assertSession()->checkboxNotChecked('edit-settings-handler-settings-target-bundles-drag-drop-text-enabled');

    $this->drupalGet('node/add/paragraphed_content_demo');
    $this->assertSession()->responseContains('<h4 class="label">Paragraphs</h4>');
    $this->submitForm([], 'Add Text');
    $this->assertSession()->responseNotContains('<strong data-drupal-selector="edit-field-paragraphs-demo-title">Paragraphs</strong>');
    $this->assertSession()->responseContains('<h4 class="label">Paragraphs</h4>');
    $edit = array(
      'title[0][value]' => 'Paragraph title',
      'moderation_state[0][state]' => 'published',
      'field_paragraphs_demo[0][subform][field_text_demo][0][value]' => 'Paragraph text',
    );
    $this->submitForm($edit, 'Add User');
    $edit = [
      'field_paragraphs_demo[1][subform][field_user_demo][0][target_id]' => $admin_user->label() . ' (' . $admin_user->id() . ')',
    ];
    $this->submitForm($edit, 'Save');

    $this->assertSession()->pageTextContains('Paragraphed article Paragraph title has been created.');
    $this->assertSession()->pageTextContains('Paragraph title');
    $this->assertSession()->pageTextContains('Paragraph text');

    // Search a nested Paragraph text.
    $this->drupalGet('paragraphs_search', ['query' => ['search_api_fulltext' => 'A search api example']]);
    $this->assertSession()->pageTextContains('Welcome to the Paragraphs Demo module!');
    // Search a node paragraph field text.
    $this->drupalGet('paragraphs_search', ['query' => ['search_api_fulltext' => 'It allows you']]);
    $this->assertSession()->pageTextContains('Welcome to the Paragraphs Demo module!');
    // Search non existent text.
    $this->drupalGet('paragraphs_search', ['query' => ['search_api_fulltext' => 'foo']]);
    $this->assertSession()->responseNotContains('Welcome to the Paragraphs Demo module!');

    // Check that the dropbutton of Nested Paragraph has the Duplicate function.
    // For now, this indicates that it is using the stable widget.
    $this->drupalGet('node/1/edit');
    $this->assertSession()->buttonExists('field_paragraphs_demo_3_subform_field_paragraphs_demo_0_duplicate');

    // Check the library paragraph.
    $this->drupalGet('admin/content/paragraphs');
    $this->assertSession()->pageTextContains('Library item');
    $this->assertSession()->pageTextContains('This is content from the library.');

    // Assert anonymous users cannot update library items.
    $this->drupalLogout();
    $this->drupalGet('admin/content/paragraphs/1/edit');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/content/paragraphs/1/delete');
    $this->assertSession()->statusCodeEquals(403);
  }

}
