<?php

namespace Drupal\Tests\paragraphs_library\Functional;

use Drupal\Core\Url;
use Drupal\Tests\paragraphs\Functional\WidgetStable\ParagraphsTestBase;

/**
 * Tests paragraphs library functionality.
 *
 * @group paragraphs_library
 */
class ParagraphsLibraryTest extends ParagraphsTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'views',
    'paragraphs_library',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->addParagraphedContentType('paragraphed_test');
  }

  /**
   * Tests the library items workflow for paragraphs.
   */
  public function testLibraryItems() {
    // Set default theme.
    \Drupal::service('theme_installer')->install(['claro']);
    $this->config('system.theme')->set('default', 'claro')->save();
    $this->loginAsAdmin(['create paragraphed_test content', 'edit any paragraphed_test content', 'administer paragraphs library']);

    // Add a Paragraph type with a text field.
    $paragraph_type = 'text_paragraph';
    $this->addParagraphsType($paragraph_type);
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);

    // Add a new library item.
    $this->drupalGet('admin/content/paragraphs');
    $this->clickLink('Add library item');
    $this->submitForm([], 'paragraphs_text_paragraph_add_more');
    $edit = [
      'label[0][value]' => 're usable paragraph label',
      'paragraphs[0][subform][field_text][0][value]' => 're_usable_text',
    ];
    $this->submitForm($edit, 'Save');
    $this->clickLink('re usable paragraph label');
    $this->assertSession()->responseContains('claro/css/base/elements.css');
    $this->clickLink('Edit');
    $this->assertSession()->responseNotContains('class="messages messages--warning"');
    $items = \Drupal::entityQuery('paragraphs_library_item')
      ->accessCheck(TRUE)
      ->sort('id', 'DESC')
      ->range(0, 1)
      ->execute();
    $library_item_id = reset($items);

    // Assert local tasks and URLs.
    $this->assertSession()->linkExists('Edit');
    $this->assertSession()->pageTextContains('Delete');
    $this->clickLink('View');
    $this->assertSession()->addressEquals(Url::fromRoute('entity.paragraphs_library_item.canonical', ['paragraphs_library_item' => $library_item_id]));
    $this->drupalGet('admin/content/paragraphs/' . $library_item_id . '/delete');
    $this->assertSession()->addressEquals(Url::fromRoute('entity.paragraphs_library_item.delete_form', ['paragraphs_library_item' => $library_item_id]));
    $this->clickLink('Edit');
    $this->assertSession()->addressEquals(Url::fromRoute('entity.paragraphs_library_item.edit_form', ['paragraphs_library_item' => $library_item_id]));

    // Check that the data is correctly stored.
    $this->drupalGet('admin/content/paragraphs');
    $this->assertSession()->pageTextContains('Used');
    $this->assertSession()->pageTextContains('Changed');
    $result = $this->cssSelect('.views-field-count');
    $this->assertEquals(trim($result[1]->getText()), '0', 'Usage info is correctly displayed.');
    $this->assertSession()->pageTextContains('Delete');
    // Check the changed field.
    $result = $this->cssSelect('.views-field-changed');
    $this->assertNotNull(trim($result[1]->getText()));
    $this->clickLink('Edit');
    $this->assertSession()->fieldExists('label[0][value]');
    $this->assertSession()->fieldExists('paragraphs[0][subform][field_text][0][value]');

    // Create a node with the library paragraph.
    $this->drupalGet('node/add/paragraphed_test');
    $this->submitForm([], 'field_paragraphs_from_library_add_more');
    $edit = [
      'title[0][value]' => 'library_test',
      'field_paragraphs[0][subform][field_reusable_paragraph][0][target_id]' => 're usable paragraph label (1)'
    ];
    $this->submitForm($edit, 'Save');

    $library_items = \Drupal::entityTypeManager()->getStorage('paragraphs_library_item')->loadByProperties(['label' => 're usable paragraph label']);
    $this->drupalGet('admin/content/paragraphs/' . current($library_items)->id() . '/edit');
    $this->assertSession()->pageTextContains('Modifications on this form will affect all existing usages of this entity.');
    $this->assertSession()->pageTextContains('Delete');

    $this->drupalGet('admin/content/paragraphs');
    $result = $this->cssSelect('.views-field-count');
    $this->assertEquals(trim($result[1]->getText()), '1', 'Usage info is correctly displayed.');

    // Assert that the paragraph is shown correctly.
    $node_one = $this->getNodeByTitle('library_test');
    $this->drupalGet('node/' . $node_one->id());
    $this->assertSession()->pageTextContains('re_usable_text');

    // Assert that the correct view mode is used.
    $notext_view_mode = \Drupal::entityTypeManager()->getStorage('entity_view_mode')->create([
      'id' => "paragraph.notext",
      'label' => 'No label view mode',
      'targetEntityType' => 'paragraph',
      'cache' => FALSE,
    ]);
    $notext_view_mode->enable();
    $notext_view_mode->save();

    $display_storage = \Drupal::entityTypeManager()->getStorage('entity_view_display');
    $notest_display = $display_storage->create([
      'status' => TRUE,
      'id' => "paragraph.$paragraph_type.notext",
      'targetEntityType' => 'paragraph',
      'bundle' => $paragraph_type,
      'mode' => 'notext',
      'content' => [],
    ]);
    $notest_display->save();

    $alternative_view_mode = \Drupal::entityTypeManager()->getStorage('entity_view_mode')->create([
      'id' => 'paragraphs_library_item.alternative',
      'label' => 'Alternative view mode',
      'targetEntityType' => 'paragraphs_library_item',
      'cache' => FALSE,
    ]);
    $alternative_view_mode->enable();
    $alternative_view_mode->save();

    $display_storage = \Drupal::entityTypeManager()->getStorage('entity_view_display');
    $alternative_display = $display_storage->create([
      'status' => TRUE,
      'id' => 'paragraphs_library_item.paragraphs_library_item.alternative',
      'targetEntityType' => 'paragraphs_library_item',
      'bundle' => 'paragraphs_library_item',
      'mode' => 'alternative',
      'content' => [
        'paragraphs' => [
          'label' => 'hidden',
          'type' => 'entity_reference_revisions_entity_view',
          'region' => 'content',
          'settings' => [
            'view_mode' => 'notext',
          ],
          'third_party_settings' => [],
          'weight' => 0,
        ],
      ],
    ]);
    $alternative_display->save();

    $this->drupalGet('node/' . $node_one->id());
    $this->assertSession()->pageTextContains('re_usable_text');

    /** @var \Drupal\Core\Entity\Entity\EntityViewDisplay $from_library_view_display */
    $from_library_view_display = $display_storage->load('paragraph.from_library.default');
    $field_reusable_paragraph_component = $from_library_view_display->getComponent('field_reusable_paragraph');
    $field_reusable_paragraph_component['settings']['view_mode'] = 'alternative';
    $from_library_view_display->setComponent('field_reusable_paragraph', $field_reusable_paragraph_component);
    $from_library_view_display->save();

    $this->drupalGet('node/' . $node_one->id());
    $this->assertSession()->pageTextNotContains('re_usable_text');

    $from_library_view_display = $display_storage->load('paragraph.from_library.default');
    $field_reusable_paragraph_component = $from_library_view_display->getComponent('field_reusable_paragraph');
    $field_reusable_paragraph_component['settings']['view_mode'] = 'default';
    $from_library_view_display->setComponent('field_reusable_paragraph', $field_reusable_paragraph_component);
    $from_library_view_display->save();

    // Create a new node with the library paragraph.
    $this->drupalGet('node/add/paragraphed_test');
    $this->submitForm([], 'field_paragraphs_from_library_add_more');
    $edit = [
      'title[0][value]' => 'library_test_new',
      'field_paragraphs[0][subform][field_reusable_paragraph][0][target_id]' => 're usable paragraph label (1)'
    ];
    $this->submitForm($edit, 'Save');
    // Assert that the paragraph is shown correctly.
    $node_two = $this->getNodeByTitle('library_test_new');
    $this->assertSession()->addressEquals('node/' . $node_two->id());
    $this->assertSession()->pageTextContains('re_usable_text');
    $this->assertSession()->pageTextNotContains('Reusable paragraph');
    $this->assertSession()->pageTextNotContains('re usable paragraph label');
    $this->assertSession()->elementTextNotContains('css', '.paragraph--type--from-library', 'Paragraphs');

    $this->drupalGet('node/' . $node_two->id() . '/edit');
    $this->submitForm([], 'field_paragraphs_from_library_add_more');
    $edit = [
      'title[0][value]' => 'library_test_new',
      'field_paragraphs[0][subform][field_reusable_paragraph][0][target_id]' => 're usable paragraph label (1)',
      'field_paragraphs[1][subform][field_reusable_paragraph][0][target_id]' => 're usable paragraph label (1)',
    ];
    $this->submitForm($edit, 'Save');

    $reusable_paragraphs_text = $this->xpath('//div[contains(@class, "field--name-field-paragraphs")]/div[@class="field__items"]/div[1]//div[@class="field__item"]/p');
    $this->assertEquals($reusable_paragraphs_text[0]->getText(), 're_usable_text');

    $second_reusable_paragraphs_text = $this->xpath('//div[contains(@class, "field--name-field-paragraphs")]/div[@class="field__items"]/div[2]//div[@class="field__item"]/p');
    $this->assertEquals($second_reusable_paragraphs_text[0]->getText(), 're_usable_text');

    // Edit the paragraph and change the text.
    $this->drupalGet('admin/content/paragraphs');

    $this->assertSession()->pageTextContains('Used');
    $result = $this->cssSelect('.views-field-count');
    $this->assertEquals(trim($result[1]->getText()), '4', 'Usage info is correctly displayed.');
    $this->assertSession()->linkNotExists('4');

    $this->clickLink('Edit');
    $this->assertSession()->pageTextContains('Modifications on this form will affect all existing usages of this entity.');
    $edit = [
      'paragraphs[0][subform][field_text][0][value]' => 're_usable_text_new',
    ];
    $this->submitForm($edit, 'Save');

    // Check in both nodes that the text is updated. Test as anonymous user, so
    // that the cache is populated.
    $this->drupalLogout();
    $this->drupalGet('node/' . $node_one->id());
    $this->assertSession()->pageTextContains('re_usable_text_new');
    $this->drupalGet('node/' . $node_two->id());
    $this->assertSession()->pageTextContains('re_usable_text_new');

    $this->loginAsAdmin(['create paragraphed_test content', 'edit any paragraphed_test content', 'administer paragraphs library']);

    // Unpublish the library item, make sure it still shows up for the curent
    // user but not for an anonymous user.
    $this->drupalGet('admin/content/paragraphs');
    $this->clickLink('Edit');
    $edit = [
      'status[value]' => FALSE,
    ];
    $this->submitForm($edit, 'Save');
    $this->drupalGet('node/' . $node_one->id());
    $this->assertSession()->pageTextContains('re_usable_text_new');

    $this->drupalLogout();
    $this->drupalGet('node/' . $node_one->id());
    $this->assertSession()->pageTextNotContains('re_usable_text_new');

    // Log in again, publish again, make sure it shows up again.
    $this->loginAsAdmin(['create paragraphed_test content', 'edit any paragraphed_test content', 'administer paragraphs library']);
    $this->drupalGet('admin/content/paragraphs');
    $this->clickLink('Edit');
    $edit = [
      'status[value]' => TRUE,
    ];
    $this->submitForm($edit, 'Save');
    $this->drupalGet('node/' . $node_one->id());
    $this->assertSession()->pageTextContains('re_usable_text_new');

    $this->drupalLogout();
    $this->drupalGet('node/' . $node_one->id());
    $this->assertSession()->pageTextContains('re_usable_text_new');

    $this->loginAsAdmin(['administer paragraphs library', 'access entity usage statistics']);
    $this->drupalGet('admin/content/paragraphs');
    $this->assertSession()->linkExists('4', 0, 'Link to usage statistics is available for user with permission.');

    $element = $this->cssSelect('th.views-field-paragraphs__target-id');
    $this->assertEquals($element[0]->getText(), 'Paragraphs', 'Paragraphs column is available.');

    $element = $this->cssSelect('.paragraphs-description .paragraphs-content-wrapper .summary-content');
    $this->assertEquals(trim($element[0]->getText()), 're_usable_text_new', 'Paragraphs summary available.');

    // Check that the deletion of library items does not cause errors.
    current($library_items)->delete();
    $this->drupalGet('node/' . $node_one->id());

    // Test paragraphs library item field UI.
    $this->loginAsAdmin([
      'administer paragraphs library',
      'administer paragraphs_library_item fields',
      'administer paragraphs_library_item form display',
      'administer paragraphs_library_item display',
      'access administration pages',
    ]);
    $this->drupalGet('admin/config/content/paragraphs_library_item');
    $this->assertSession()->linkExists('Manage fields');
    $this->assertSession()->linkExists('Manage form display');
    $this->assertSession()->linkExists('Manage display');
    $this->assertSession()->buttonExists('Save configuration');
    // Assert that users can create fields to
    $this->clickLink('Manage fields');
    $this->clickLink('Create a new field');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('plugin does not exist');
    $this->drupalGet('admin/config/content');
    $this->clickLink('Paragraphs library item settings');
  }

  /**
   * Tests converting Library item into a paragraph.
   */
  public function testConvertLibraryItemIntoParagraph() {
    $this->loginAsAdmin([
      'create paragraphed_test content',
      'edit any paragraphed_test content',
      'administer paragraphs library',
    ]);

    // Add a Paragraph type with a text field.
    $paragraph_type = 'text';
    $this->addParagraphsType($paragraph_type);
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);

    // Add a new library item.
    $this->drupalGet('admin/content/paragraphs');
    $this->clickLink('Add library item');
    $this->submitForm([], 'paragraphs_text_add_more');
    $edit = [
      'label[0][value]' => 'reusable paragraph label',
      'paragraphs[0][subform][field_text][0][value]' => 'reusable_text',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Paragraph reusable paragraph label has been created.');

    // Add created library item to a node.
    $this->drupalGet('node/add/paragraphed_test');
    $this->submitForm([], 'field_paragraphs_from_library_add_more');
    $edit = [
      'title[0][value]' => 'Node with converted library item',
      'field_paragraphs[0][subform][field_reusable_paragraph][0][target_id]' => 'reusable paragraph label',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('paragraphed_test Node with converted library item has been created.');
    $this->assertSession()->pageTextContains('reusable_text');

    // Convert library item to paragraph.
    $this->clickLink('Edit');
    $this->submitForm([], 'field_paragraphs_0_unlink_from_library');
    $this->assertSession()->fieldExists('field_paragraphs[0][subform][field_text][0][value]');
    $this->assertSession()->fieldNotExists('field_paragraphs[0][subform][field_reusable_paragraph][0][target_id]');
    $this->assertSession()->pageTextContains('reusable_text');
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('reusable_text');
  }

  /**
   * Tests converting paragraph item into library.
   */
  public function testConvertParagraphIntoLibrary() {
    $user = $this->createUser(array_merge($this->admin_permissions, [
      'create paragraphed_test content',
      'edit any paragraphed_test content',
      'administer paragraphs library',
      'administer paragraphs types',
    ]));
    $this->drupalLogin($user);

    $paragraph_type = 'text';
    $this->addParagraphsType($paragraph_type);
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);

    $edit = ['allow_library_conversion' => 1];
    $this->drupalGet('admin/structure/paragraphs_type/text');
    $this->submitForm($edit, 'Save');

    // Adding library item is available with the administer library permission.
    $this->drupalGet('node/add/paragraphed_test');
    $this->submitForm([], 'Add text');
    $this->assertSession()->buttonExists('field_paragraphs_0_promote_to_library');
    $this->drupalGet('admin/content/paragraphs/add/default');
    $this->assertSession()->statusCodeEquals(200);

    // Adding library item is not available without appropriate permissions.
    $user_roles = $user->getRoles(TRUE);
    $user_role = end($user_roles);
    user_role_revoke_permissions($user_role, ['administer paragraphs library']);
    $this->drupalGet('node/add/paragraphed_test');
    $this->submitForm([], 'Add text');
    $this->assertSession()->buttonNotExists('field_paragraphs_0_promote_to_library');
    $this->drupalGet('admin/content/paragraphs/add/default');
    $this->assertSession()->statusCodeEquals(403);

    // Enabling a dummy behavior plugin for testing the item label creation.
    $edit = [
      'behavior_plugins[test_text_color][enabled]' => TRUE,
    ];
    $this->drupalGet('admin/structure/paragraphs_type/text');
    $this->submitForm($edit, 'Save');
    // Assign "create paragraph library item" permission to a user.
    user_role_grant_permissions($user_role, ['create paragraph library item']);
    $this->drupalGet('admin/content/paragraphs/add/default');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('node/add/paragraphed_test');
    $this->submitForm([], 'Add text');
    $this->assertSession()->buttonExists('field_paragraphs_0_promote_to_library');
    $this->assertSession()->responseContains('Promote to library');
    $edit = [
      'field_paragraphs[0][subform][field_text][0][value]' => 'Random text for testing converting into library.',
    ];
    $this->submitForm($edit, 'field_paragraphs_0_promote_to_library');
    $this->assertSession()->pageTextContains('From library');
    $this->assertSession()->pageTextContains('Reusable paragraph');
    $this->assertSession()->fieldExists('field_paragraphs[0][subform][field_reusable_paragraph][0][target_id]');
    $edit = [
      'title[0][value]' => 'TextParagraphs',
    ];
    $this->assertSession()->buttonNotExists('field_paragraphs_0_promote_to_library');
    $this->assertSession()->buttonExists('field_paragraphs_0_unlink_from_library');
    $this->submitForm($edit, 'Save');
    $this->drupalGet('node/1');
    $this->assertSession()->pageTextContains('Random text for testing converting into library.');

    // Create library item from existing paragraph item.
    $this->drupalGet('node/add/paragraphed_test');
    $this->submitForm([], 'Add text');
    $edit = [
      'title[0][value]' => 'NodeTitle',
      'field_paragraphs[0][subform][field_text][0][value]' => 'Random text for testing converting into library.',
    ];
    $this->submitForm($edit, 'Save');
    $node = $this->getNodeByTitle('NodeTitle');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'field_paragraphs_0_promote_to_library');
    user_role_grant_permissions($user_role, ['administer paragraphs library']);
    $this->drupalGet('/admin/content/paragraphs');
    $this->assertSession()->pageTextContains('Text');
    $this->assertSession()->pageTextContains('Random text for testing converting into library.');

    // Test disallow convesrion.
    $edit = ['allow_library_conversion' => FALSE];
    $this->drupalGet('admin/structure/paragraphs_type/text');
    $this->submitForm($edit, 'Save');

    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = \Drupal::service('config.factory');
    $third_party = $config_factory->get('paragraphs.paragraphs_type.text')->get('third_party_settings');
    $this->assertFalse(isset($third_party['paragraphs_library']['allow_library_conversion']));

    $this->drupalGet('node/add/paragraphed_test');
    $this->submitForm([], 'Add text');
    $this->assertSession()->responseNotContains('Promote to library');

    // Test that the checkbox is not visible on from_library.
    $this->drupalGet('admin/structure/paragraphs_type/from_library');
    $this->assertSession()->fieldNotExists('allow_library_conversion');
  }

  /**
   * Tests the library paragraphs summary preview.
   */
  public function testLibraryItemParagraphsSummary() {
    $this->loginAsAdmin(['create paragraphed_test content', 'edit any paragraphed_test content', 'administer paragraphs library']);
    $paragraph_type = 'text';
    $this->addParagraphsType($paragraph_type);
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);

    // Create paragraph type Nested test.
    $this->addParagraphsType('nested_test');

    static::fieldUIAddNewField('admin/structure/paragraphs_type/nested_test', 'paragraphs', 'Paragraphs', 'entity_reference_revisions', [
      'settings[target_type]' => 'paragraph',
      'cardinality' => '-1',
    ], []);
    $this->drupalGet('admin/structure/paragraphs_type/nested_test/form-display');
    $edit = [
      'fields[field_paragraphs][type]' => 'paragraphs',
    ];
    $this->submitForm($edit, 'Save');
    $this->drupalGet('admin/content/paragraphs/add/default');
    $this->submitForm([], 'paragraphs_nested_test_add_more');
    $this->submitForm([], 'paragraphs_0_subform_field_paragraphs_text_add_more');
    $edit = [
      'label[0][value]' => 'Test nested',
      'paragraphs[0][subform][field_paragraphs][0][subform][field_text][0][value]' => 'test text paragraph',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('test text paragraph');

    // Assert that the user with the access content permission can see the
    // paragraph type label.
    $user = $this->drupalCreateUser([
      'access content',
      'administer paragraphs library'
    ]);
    $this->drupalLogin($user);
    $this->drupalGet('admin/content/paragraphs');
    $paragraph_type = $this->xpath('//table/tbody/tr/td[2]');
    $this->assertEquals(trim(strip_tags($paragraph_type[0]->getText())), 'nested_test');
  }

  /**
   * Tests the library item validation.
   */
  public function testLibraryitemValidation() {
    $this->loginAsAdmin([
      'create paragraphed_test content',
      'edit any paragraphed_test content',
      'administer paragraphs library'
    ]);

    // Add a Paragraph type with a text field.
    $paragraph_type = 'text_paragraph';
    $this->addParagraphsType($paragraph_type);
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);

    // Add a new library item.
    $this->drupalGet('admin/content/paragraphs');
    $this->clickLink('Add library item');

    // Check the label validation.
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('Label field is required.');
    $edit = [
      'label[0][value]' => 're usable paragraph label',
    ];
    $this->submitForm($edit, 'Save');

    // Check the paragraph validation.
    $this->assertSession()->pageTextContains('Paragraphs field is required.');
    $this->submitForm([], 'paragraphs_text_paragraph_add_more');
    $edit['paragraphs[0][subform][field_text][0][value]'] = 're_usable_text';

    // Check that the library item is created.
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Paragraph re usable paragraph label has been created.');
    $this->clickLink('Edit');
    $edit = [
      'paragraphs[0][subform][field_text][0][value]' => 'new text',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Paragraph re usable paragraph label has been updated.');
  }

  /**
   * Tests the validation of paragraphs referencing library items.
   */
  public function testLibraryReferencingParagraphValidation() {
    $this->loginAsAdmin([
      'create paragraphed_test content',
      'edit any paragraphed_test content',
      'administer paragraphs library'
    ]);
    $paragraph_type = 'text';
    $this->addParagraphsType($paragraph_type);
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);

    // Add a library item with paragraphs type "Text".
    $this->drupalGet('admin/content/paragraphs');
    $this->clickLink('Add library item');
    $this->submitForm([], 'paragraphs_text_add_more');
    $edit = [
      'label[0][value]' => 'reusable paragraph label',
      'paragraphs[0][subform][field_text][0][value]' => 'reusable_text',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Paragraph reusable paragraph label has been created.');

    // Create a node with a "From library" paragraph referencing the library
    // item.
    $this->drupalGet('node/add/paragraphed_test');
    $this->submitForm([], 'field_paragraphs_from_library_add_more');
    $edit = [
      'title[0][value]' => 'library_test',
      'field_paragraphs[0][subform][field_reusable_paragraph][0][target_id]' => 'reusable paragraph label',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('paragraphed_test library_test has been created.');

    // Disallow the paragraphs type "Text" for the used content type.
    $this->drupalGet('admin/structure/types/manage/paragraphed_test/fields/node.paragraphed_test.field_paragraphs');
    $edit = [
      'settings[handler_settings][negate]' => '0',
      'settings[handler_settings][target_bundles_drag_drop][from_library][enabled]' => '1',
      'settings[handler_settings][target_bundles_drag_drop][text][enabled]' => FALSE,
    ];
    $this->submitForm($edit, 'Save settings');
    $this->assertSession()->pageTextContains('Saved field_paragraphs configuration.');

    // Check that the node now fails validation.
    $node = $this->getNodeByTitle('library_test');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm([], 'Save');
    $this->assertSession()->addressEquals('node/' . $node->id() . '/edit');
    $this->assertSession()->pageTextContains('The Reusable paragraph field cannot contain a text paragraph, because the parent field_paragraphs field disallows it.');
  }

  /**
   * Test paragraphs library item revisions.
   */
  public function testLibraryItemRevisions() {
    $this->loginAsAdmin([
      'create paragraphed_test content',
      'edit any paragraphed_test content',
      'administer paragraphs library',
    ]);

    $this->addParagraphsType('test_content');
    $this->addParagraphsType('nested_paragraph');

    $this->fieldUIAddNewField('admin/structure/paragraphs_type/test_content', 'paragraphs_text', 'Test content', 'text_long', [], []);

    // Add nested paragraph field.
    $this->fieldUIAddNewField('admin/structure/paragraphs_type/nested_paragraph', 'err_field', 'Nested', 'field_ui:entity_reference_revisions:paragraph', [
      'settings[target_type]' => 'paragraph',
      'cardinality' => '-1',
    ], []);

    // Add nested paragraph directly in library.
    $this->drupalGet('admin/content/paragraphs/add/default');
    $this->submitForm([], 'paragraphs_nested_paragraph_add_more');
    $this->submitForm([], 'paragraphs_0_subform_field_err_field_test_content_add_more');
    $edit = [
      'label[0][value]' => 'Test revisions nested original',
      'paragraphs[0][subform][field_err_field][0][subform][field_paragraphs_text][0][value]' => 'Example text for revision original.',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Paragraph Test revisions nested original has been created.');

    // Check revisions tab.
    $this->clickLink('Test revisions nested original');
    $this->clickLink('Revisions');
    $this->assertSession()->titleEquals('Revisions for Test revisions nested original | Drupal');

    // Edit library item, check that new revision is enabled as default.
    $this->clickLink('Edit');
    $this->assertSession()->checkboxChecked('edit-revision');
    $edit = [
      'label[0][value]' => 'Test revisions nested first change',
      'paragraphs[0][subform][field_err_field][0][subform][field_paragraphs_text][0][value]' => 'Example text for revision first change.',
    ];
    $this->submitForm($edit, 'Save');

    // Check previous revision.
    $storage = \Drupal::entityTypeManager()->getStorage('paragraphs_library_item');
    $date_formatter = \Drupal::service('date.formatter');
    $this->clickLink('Test revisions nested first change');
    $this->clickLink('Revisions');
   $this->assertSession()->titleEquals('Revisions for Test revisions nested first change | Drupal');
    $revision = $storage->loadRevision(1);
    $this->assertSession()->pageTextContains('Test revisions nested original by ' . $this->admin_user->getAccountName());
    $this->assertSession()->pageTextContains($date_formatter->format($revision->getChangedTime(), 'short') . ': ' . $revision->label());
    $this->clickLink($date_formatter->format($revision->getChangedTime(), 'short'), 1);
    $this->assertSession()->pageTextContains('Test revisions nested original');
    $this->assertSession()->pageTextContains('Example text for revision original');
    $this->clickLink('Revisions');

    // Test reverting revision.
    $this->clickLink('Revert');
    $this->assertSession()->responseContains('Are you sure you want to revert revision from ' . $date_formatter->format($revision->getChangedTime()) . '?');
    $this->submitForm([], 'Revert');
    $this->assertSession()->pageTextContains('Test revisions nested original has been reverted to the revision from ' . $date_formatter->format($revision->getChangedTime()) . '.');

    // Check current revision.
    $current_revision = $storage->loadRevision(3);
    $this->clickLink($date_formatter->format($current_revision->getChangedTime(), 'short'));
    $this->assertSession()->pageTextContains('Example text for revision original');
    $this->clickLink('Revisions');

    // Test deleting revision.
    $revision_for_deleting = $storage->loadRevision(2);
    $this->clickLink('Delete');
    $this->assertSession()->responseContains('Are you sure you want to delete revision from ' . $date_formatter->format($revision_for_deleting->getChangedTime()));
    $this->submitForm([], 'Delete');
    $this->assertSession()->pageTextContains('Revision from ' . $date_formatter->format($revision_for_deleting->getChangedTime()) .' has been deleted.');
  }

}
