<?php

namespace Drupal\Tests\paragraphs_type_permissions\Functional;

use Drupal\image\Entity\ImageStyle;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;
use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;
use Drupal\Tests\paragraphs\Traits\ParagraphsCoreVersionUiTestTrait;
use Drupal\user\Entity\Role;

/**
 * Tests the paragraphs type permissions.
 *
 * @group paragraphs
 */
class ParagraphsTypePermissionsTest extends BrowserTestBase {

  use FieldUiTestTrait, ParagraphsCoreVersionUiTestTrait, ParagraphsTestBaseTrait;

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
    'node',
    'paragraphs_type_permissions',
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
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('local_actions_block');
    ConfigurableLanguage::create(['id' => 'de', 'label' => '1German'])->save();
    ConfigurableLanguage::create(['id' => 'fr', 'label' => '2French'])->save();
    $this->addParagraphedContentType('paragraphed_content_demo', 'field_paragraphs_demo');
    $admin_user = $this->drupalCreateUser(array(
      'administer site configuration',
      'administer content translation',
      'administer languages',
      'administer node fields',
      'administer content types',
      'administer paragraphs types',
      'administer node form display',
      'administer paragraph fields',
      'administer paragraph form display',
    ));
    $this->drupalLogin($admin_user);
    $this->addParagraphsType('nested_paragraph');
    $this->addParagraphsField('nested_paragraph', 'field_paragraphs_demo', 'paragraph');
    $this->addParagraphsType('images');
    static::fieldUIAddNewField('admin/structure/paragraphs_type/images', 'images_demo', 'Images', 'image', ['cardinality' => -1], ['settings[alt_field]' => FALSE]);
    $this->addParagraphsType('text_image');
    static::fieldUIAddNewField('admin/structure/paragraphs_type/text_image', 'image_demo', 'Images', 'image', [], ['settings[alt_field]' => FALSE]);
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

    $display_options = [
      'type' => 'image',
      'settings' => ['image_style' => 'medium', 'image_link' => 'file'],
    ];
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display = $display_repository->getViewDisplay('paragraph', 'images');
    $display->setComponent('field_images_demo', $display_options)
      ->save();

    $display_options = [
      'type' => 'image',
      'settings' => ['image_style' => 'large', 'image_link' => 'file'],
    ];
    $display = $display_repository->getViewDisplay('paragraph', 'text_image');
    $display->setComponent('field_image_demo', $display_options)
      ->save();
  }

  /**
   * Tests paragraphs type permissions for anonymous and authenticated users.
   */
  public function testAnonymousParagraphsTypePermissions() {
    // Create an authenticated user without special permissions for test.
    $authenticated_user = $this->drupalCreateUser();
    // Create an admin user for test.
    $admin_user = $this->drupalCreateUser(array(
      'administer site configuration',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer paragraphs types',
      'administer paragraph form display',
      'create paragraphed_content_demo content',
      'edit any paragraphed_content_demo content',
      'bypass paragraphs type content access',
      'administer node form display',
    ));
    $this->drupalLogin($admin_user);

    // Enable the publish/unpublish checkbox fields.
    $paragraph_types = [
      'text_image',
      'images',
      'text',
    ];
    foreach ($paragraph_types as $paragraph_type) {
      $form_display = \Drupal::service('entity_display.repository')->getFormDisplay('paragraph', $paragraph_type);
      $form_display->setComponent('status', [
          'type' => 'boolean_checkbox'
        ])
        ->save();
    }

    // Create a node with some Paragraph types.
    $this->drupalGet('node/add/paragraphed_content_demo');
    $this->submitForm([], 'Add text_image');
    $this->submitForm([], 'Add images');
    $this->submitForm([], 'Add text');

    $image_text = $this->getTestFiles('image')[0];
    $this->submitForm([
      'files[field_paragraphs_demo_0_subform_field_image_demo_0]' => $image_text->uri,
    ], 'Upload');
    $images = $this->getTestFiles('image')[1];
    $this->submitForm([
      'files[field_paragraphs_demo_1_subform_field_images_demo_0][]' => $images->uri,
    ], 'Upload');
    $edit = [
      'title[0][value]' => 'paragraph node title',
      'field_paragraphs_demo[0][subform][field_text_demo][0][value]' => 'Paragraph type Image + Text',
      'field_paragraphs_demo[2][subform][field_text_demo][0][value]' => 'Paragraph type Text',
    ];
    $this->submitForm($edit, 'Save');

    // Get the node to edit it later.
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);

    /** @var \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator */
    $file_url_generator = \Drupal::service('file_url_generator');

    // Get the images data to check for their presence.
    $image_text_tag = $file_url_generator->transformRelative(ImageStyle::load('large')->buildUrl('public://' . date('Y-m') . '/image-test.png'));
    $images_tag = $file_url_generator->transformRelative(ImageStyle::load('medium')->buildUrl('public://' . date('Y-m') . '/image-test_0.png'));

    // Check that all paragraphs are shown for admin user.
    $this->assertSession()->responseContains($image_text_tag);
    $this->assertSession()->responseContains($images_tag);
    $this->assertSession()->pageTextContains('Paragraph type Image + Text');
    $this->assertSession()->pageTextContains('Paragraph type Text');

    // Logout, check that no paragraphs are shown for anonymous user.
    $this->drupalLogout();
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseNotContains($image_text_tag);
    $this->assertSession()->responseNotContains($images_tag);
    $this->assertSession()->pageTextNotContains('Paragraph type Image + Text');
    $this->assertSession()->pageTextNotContains('Paragraph type Text');

    // Login as authenticated user, check that no paragraphs are shown for him.
    $this->drupalLogin($authenticated_user);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseNotContains($image_text_tag);
    $this->assertSession()->responseNotContains($images_tag);
    $this->assertSession()->pageTextNotContains('Paragraph type Image + Text');
    $this->assertSession()->pageTextNotContains('Paragraph type Text');

    // Login as admin
    $this->drupalLogout();
    $this->drupalLogin($admin_user);

    // Set edit mode to open.
    $this->drupalGet('admin/structure/types/manage/paragraphed_content_demo/form-display');
    $this->submitForm([], "field_paragraphs_demo_settings_edit");
    $edit = ['fields[field_paragraphs_demo][settings_edit_form][settings][edit_mode]' => 'open'];
    $this->submitForm($edit, 'Save');

    // Unpublish the 'Image + Text' paragraph type.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->checkboxChecked('edit-field-paragraphs-demo-0-subform-status-value');
    $edit = [
      'field_paragraphs_demo[0][subform][status][value]' => FALSE,
    ];
    $this->submitForm($edit, 'Save');

    // Check that 'Image + Text' paragraph is not shown anymore for admin user.
    $this->assertSession()->responseNotContains($image_text_tag);
    $this->assertSession()->responseContains($images_tag);
    $this->assertSession()->pageTextNotContains('Paragraph type Image + Text');
    $this->assertSession()->pageTextContains('Paragraph type Text');

    $this->drupalLogout();

    // Add permissions to anonymous user to view only 'Image + Text' and
    // 'Text' paragraph contents.
    /** @var \Drupal\user\RoleInterface $anonymous_role */
    $anonymous_role = Role::load('anonymous');
    $anonymous_role->grantPermission('view paragraph content text_image');
    $anonymous_role->grantPermission('view paragraph content text');
    $anonymous_role->save();

    // Add permissions to authenticated user to view only 'Image + Text' and
    // 'Images' paragraph contents.
    /** @var \Drupal\user\RoleInterface $authenticated_role */
    $authenticated_role = Role::load('authenticated');
    $authenticated_role->grantPermission('view paragraph content text_image');
    $authenticated_role->grantPermission('view paragraph content images');
    $authenticated_role->save();

    // Check that the anonymous user can only view the 'Text' paragraph.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseNotContains($image_text_tag);
    $this->assertSession()->responseNotContains($images_tag);
    $this->assertSession()->pageTextNotContains('Paragraph type Image + Text');
    $this->assertSession()->pageTextContains('Paragraph type Text');

    // Check that the authenticated user can only view the 'Images' paragraph.
    $this->drupalLogin($authenticated_user);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseNotContains($image_text_tag);
    $this->assertSession()->responseContains($images_tag);
    $this->assertSession()->pageTextNotContains('Paragraph type Image + Text');
    $this->assertSession()->pageTextNotContains('Paragraph type Text');

    // Check the authenticated user with edit permission.
    $authenticated_role->grantPermission('update paragraph content text_image');
    $authenticated_role->grantPermission('bypass node access');
    $authenticated_role->save();
    $this->drupalLogin($authenticated_user);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->responseContains('Image + Text');
    $this->assertSession()->pageTextContains('Paragraph type Image + Text');
    $this->assertSession()->pageTextContains('You are not allowed to remove this Paragraph.');
    $this->assertSession()->pageTextContains('Published');
    $this->assertSession()->pageTextContains('Images');
    $this->assertSession()->pageTextContains('You are not allowed to edit or remove this Paragraph.');
    $this->assertSession()->responseContains('paragraphs-collapsed-description"><div class="paragraphs-content-wrapper"><span class="summary-content">image-test_0.png<');
    $this->assertSession()->responseNotContains('paragraphs-collapsed-description"><div class="paragraphs-content-wrapper"><span class="summary-content">Paragraph type Text<');

    // Check that the paragraph is collapsed by asserting the content summary.
    $authenticated_role->grantPermission('view paragraph content text');
    $authenticated_role->save();
    $this->drupalLogin($authenticated_user);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->pageTextContains('You are not allowed to edit or remove this Paragraph.');
    $this->assertSession()->responseContains('paragraphs-collapsed-description"><div class="paragraphs-content-wrapper"><span class="summary-content">image-test_0.png<');
    $this->assertSession()->responseContains('paragraphs-collapsed-description"><div class="paragraphs-content-wrapper"><span class="summary-content">Paragraph type Text<');
  }

}
