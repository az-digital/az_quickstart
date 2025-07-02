<?php

namespace Drupal\Tests\webform_ui\Functional;

use Drupal\Core\Url;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform UI element.
 *
 * @group webform_ui
 */
class WebformUiElementTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_ui', 'webform_test_element'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_date'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Disable description help icon.
    $this->config('webform.settings')->set('ui.description_help', FALSE)->save();
  }

  /**
   * Tests element.
   */
  public function testElements() {
    global $base_path;

    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    $webform_contact = Webform::load('contact');

    /* ********************************************************************** */
    // Multiple.
    /* ********************************************************************** */

    // Check multiple enabled before submission.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/name/edit');
    $assert_session->responseContains('<select data-drupal-selector="edit-properties-multiple-container-cardinality" id="edit-properties-multiple-container-cardinality" name="properties[multiple][container][cardinality]" class="form-select">');
    $assert_session->responseNotContains('<em>There is data for this element in the database. This setting can no longer be changed.</em>');

    // Check multiple disabled after submission.
    $this->postSubmissionTest($webform_contact);
    $this->drupalGet('/admin/structure/webform/manage/contact/element/name/edit');
    $assert_session->responseNotContains('<select data-drupal-selector="edit-properties-multiple-container-cardinality" id="edit-properties-multiple-container-cardinality" name="properties[multiple][container][cardinality]" class="form-select">');
    $assert_session->responseContains('<select data-drupal-selector="edit-properties-multiple-container-cardinality" disabled="disabled" id="edit-properties-multiple-container-cardinality" name="properties[multiple][container][cardinality]" class="form-select">');
    $assert_session->responseContains('<em>There is data for this element in the database. This setting can no longer be changed.</em>');

    /* ********************************************************************** */
    // Reordering.
    /* ********************************************************************** */

    // Check original contact element order.
    $this->assertEquals(['name', 'email', 'subject', 'message', 'actions'], array_keys($webform_contact->getElementsDecodedAndFlattened()));

    // Check updated (reverse) contact element order.
    /** @var \Drupal\webform\WebformInterface $webform_contact */
    $this->drupalGet('/admin/structure/webform/manage/contact');
    $edit = [
      'webform_ui_elements[message][weight]' => 0,
      'webform_ui_elements[subject][weight]' => 1,
      'webform_ui_elements[email][weight]' => 2,
      'webform_ui_elements[name][weight]' => 3,
    ];
    $this->submitForm($edit, 'Save elements');

    \Drupal::entityTypeManager()->getStorage('webform_submission')->resetCache();
    \Drupal::entityTypeManager()->getStorage('webform')->resetCache();
    $webform_contact = Webform::load('contact');
    $this->assertEquals(['message', 'subject', 'email', 'name', 'actions'], array_keys($webform_contact->getElementsDecodedAndFlattened()));

    /* ********************************************************************** */
    // Hierarchy.
    /* ********************************************************************** */

    // Create a simple test form.
    $values = ['id' => 'test'];
    $elements = [
      'details_01' => [
        '#type' => 'details',
        '#title' => 'details_01',
        'text_field_01' => [
          '#type' => 'textfield',
          '#title' => 'textfield_01',
        ],
      ],
      'details_02' => [
        '#type' => 'details',
        '#title' => 'details_02',
        'text_field_02' => [
          '#type' => 'textfield',
          '#title' => 'textfield_02',
        ],
      ],
    ];
    $this->createWebform($values, $elements);
    $this->drupalGet('/admin/structure/webform/manage/test');

    // Check setting container to itself displays an error.
    $this->drupalGet('/admin/structure/webform/manage/test');
    $edit = ['webform_ui_elements[details_01][parent_key]' => 'details_01'];
    $this->submitForm($edit, 'Save elements');
    $assert_session->responseContains('Parent <em class="placeholder">details_01</em> key is not valid.');

    // Check setting containers to one another displays an error.
    $this->drupalGet('/admin/structure/webform/manage/test');
    $edit = [
      'webform_ui_elements[details_01][parent_key]' => 'details_02',
      'webform_ui_elements[details_02][parent_key]' => 'details_01',
    ];
    $this->submitForm($edit, 'Save elements');
    $assert_session->responseContains('Parent <em class="placeholder">details_01</em> key is not valid.');
    $assert_session->responseContains('Parent <em class="placeholder">details_02</em> key is not valid.');

    /* ********************************************************************** */
    // Required.
    /* ********************************************************************** */

    // Check name is required.
    $this->drupalGet('/admin/structure/webform/manage/contact');
    $assert_session->checkboxChecked('edit-webform-ui-elements-name-required');

    // Check name is not required.
    $this->drupalGet('/admin/structure/webform/manage/contact');
    $edit = ['webform_ui_elements[name][required]' => FALSE];
    $this->submitForm($edit, 'Save elements');
    $assert_session->checkboxNotChecked('edit-webform-ui-elements-name-required');

    /* ********************************************************************** */
    // Notes.
    /* ********************************************************************** */

    // Add admin notes to contact name element.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/name/edit');
    $edit = ['properties[admin_notes][value][value]' => 'This is an admin note.'];
    $this->submitForm($edit, 'Save');
    $assert_session->responseContains('<span data-drupal-selector="edit-webform-ui-elements-name-title-notes" class="webform-element-help js-webform-element-help" role="tooltip" tabindex="0" aria-label="Your Name" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;Your Name&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;This is an admin note.&lt;/div&gt;"><span aria-hidden="true">?</span></span>');

    /* ********************************************************************** */
    // CRUD.
    /* ********************************************************************** */

    // Check that 'Save + Add element' is only visible in dialogs.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add/textfield');
    $assert_session->responseNotContains('Save + Add element');
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add/textfield', ['query' => ['_wrapper_format' => 'drupal_dialog']]);
    $assert_session->responseContains('Save + Add element');

    // Create element.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add/textfield');
    $edit = ['key' => 'test', 'properties[title]' => 'Test'];
    $this->submitForm($edit, 'Save');

    // Check elements URL contains ?update query string parameter.
    $assert_session->addressEquals(Url::fromRoute('entity.webform.edit_form', ['webform' => 'contact'], ['query' => ['update' => 'test']]));

    // Check that save elements removes ?update query string parameter.
    $this->submitForm([], 'Save elements');

    // Check that save elements removes ?update query string parameter.
    $assert_session->addressEquals(Url::fromRoute('entity.webform.edit_form', ['webform' => 'contact'], ['query' => ['update' => 'test']]));

    // Create validate unique element.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add/textfield');
    $edit = ['key' => 'test', 'properties[title]' => 'Test'];
    $this->submitForm($edit, 'Save');
    $assert_session->responseContains('The machine-readable name is already in use. It must be unique.');

    // Check read element.
    $this->drupalGet('/webform/contact');
    $assert_session->responseContains('<label for="edit-test">Test</label>');
    $assert_session->responseContains('<input data-drupal-selector="edit-test" type="text" id="edit-test" name="test" value="" size="60" maxlength="255" class="form-text" />');

    // Update element.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/test/edit');
    $edit = ['properties[title]' => 'Test 123', 'properties[default_value]' => 'This is a default value'];
    $this->submitForm($edit, 'Save');

    // Check elements URL contains ?update query string parameter.
    $assert_session->addressEquals(Url::fromRoute('entity.webform.edit_form', ['webform' => 'contact'], ['query' => ['update' => 'test']]));

    // Check element updated.
    $this->drupalGet('/webform/contact');
    $assert_session->responseContains('<label for="edit-test">Test 123</label>');
    $assert_session->responseContains('<input data-drupal-selector="edit-test" type="text" id="edit-test" name="test" value="This is a default value" size="60" maxlength="255" class="form-text" />');

    // Check that 'test' element is being added to the webform_submission_data table.
    $this->drupalGet('/webform/contact/test');
    $this->submitForm([], 'Send message');
    $this->assertEquals(1, \Drupal::database()->query("SELECT COUNT(sid) FROM {webform_submission_data} WHERE webform_id='contact' AND name='test'")->fetchField());

    // Check delete element.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/test/delete');
    $this->submitForm([], 'Delete');
    $this->drupalGet('/webform/contact');
    $assert_session->responseNotContains('<label for="edit-test">Test 123</label>');
    $assert_session->responseNotContains('<input data-drupal-selector="edit-test" type="text" id="edit-test" name="test" value="This is a default value" size="60" maxlength="255" class="form-text" />');

    // Check that 'test' element values were deleted from the webform_submission_data table.
    $this->assertEquals(0, \Drupal::database()->query("SELECT COUNT(sid) FROM {webform_submission_data} WHERE webform_id='contact' AND name='test'")->fetchField());

    // Check access allowed to textfield element.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add/textfield');
    $assert_session->statusCodeEquals(200);

    // Check access denied to password element, which is disabled by default.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add/password');
    $assert_session->statusCodeEquals(403);

    /* ********************************************************************** */
    // Change type.
    /* ********************************************************************** */

    // Check create element.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add/textfield');
    $edit = ['key' => 'test', 'properties[title]' => 'Test'];
    $this->submitForm($edit, 'Save');

    // Check element type.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/test/edit');
    // Check change element type link.
    $assert_session->responseContains('Text field <a href="' . $base_path . 'admin/structure/webform/manage/contact/element/test/change" class="button button--small webform-ajax-link" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:800,&quot;dialogClass&quot;:&quot;webform-ui-dialog&quot;}" data-drupal-selector="edit-change-type" id="edit-change-type">Change</a>');
    // Check text field has description.
    $assert_session->responseContains('A short description of the element used as help for the user when they use the webform.');

    // Check change element types.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/test/change');
    $assert_session->responseContains('Hidden');
    $this->assertCssSelect('a[href$="admin/structure/webform/manage/contact/element/test/edit?type=hidden"][data-dialog-type][data-dialog-options][data-drupal-selector="edit-elements-hidden-operation"]');
    $assert_session->responseContains('Search');
    $this->assertCssSelect('a[href$="admin/structure/webform/manage/contact/element/test/edit?type=search"][data-dialog-type][data-dialog-options][data-drupal-selector="edit-elements-search-operation"]');
    $assert_session->responseContains('Telephone');
    $this->assertCssSelect('a[href$="admin/structure/webform/manage/contact/element/test/edit?type=tel"][data-dialog-type][data-dialog-options][data-drupal-selector="edit-elements-tel-operation"]');
    $assert_session->responseContains('URL');
    $this->assertCssSelect('a[href$="admin/structure/webform/manage/contact/element/test/edit?type=url"][data-dialog-type][data-dialog-options][data-drupal-selector="edit-elements-url-operation"]');

    // Check change element type.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/test/edit', ['query' => ['type' => 'hidden']]);
    // Check hidden has no description.
    $assert_session->responseNotContains('A short description of the element used as help for the user when they use the webform.');
    $assert_session->responseContains('Hidden <a href="' . $base_path . 'admin/structure/webform/manage/contact/element/test/edit" class="button button--small webform-ajax-link" data-dialog-type="dialog" data-dialog-renderer="off_canvas" data-dialog-options="{&quot;width&quot;:600,&quot;dialogClass&quot;:&quot;ui-dialog-off-canvas webform-off-canvas&quot;}" data-drupal-selector="edit-cancel" id="edit-cancel">Cancel</a>');
    $assert_session->responseContains('(Changing from <em class="placeholder">Text field</em>)');

    // Change the element type.
    $options = ['query' => ['type' => 'hidden']];
    $this->drupalGet('/admin/structure/webform/manage/contact/element/test/edit', $options);
    $this->submitForm([], 'Save');

    // Change the element type from 'textfield' to 'hidden'.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/test/edit');

    // Check change element type link.
    $assert_session->responseContains('Hidden <a href="' . $base_path . 'admin/structure/webform/manage/contact/element/test/change" class="button button--small webform-ajax-link" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:800,&quot;dialogClass&quot;:&quot;webform-ui-dialog&quot;}" data-drupal-selector="edit-change-type" id="edit-change-type">Change</a>');

    // Check color element that does not have related type and return 404.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add/color');
    $edit = ['key' => 'test_color', 'properties[title]' => 'Test color'];
    $this->submitForm($edit, 'Save');
    $this->drupalGet('/admin/structure/webform/manage/contact/element/test_color/change');
    $assert_session->statusCodeEquals(404);

    /* ********************************************************************** */
    // Date.
    /* ********************************************************************** */

    // Check GNU Date Input Format validation.
    $this->drupalGet('/admin/structure/webform/manage/test_element_date/element/date_min_max_dynamic/edit');
    $edit = ['properties[default_value]' => 'not a valid date'];
    $this->submitForm($edit, 'Save');
    $assert_session->responseContains('The Default value could not be interpreted in <a href="https://www.gnu.org/software/tar/manual/html_chapter/tar_7.html#Date-input-formats">GNU Date Input Format</a>.');

    /* ********************************************************************** */
    // Off-canvas width.
    /* ********************************************************************** */

    // Check add off-canvas element width is 800.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add');
    $this->assertCssSelect('[href$="/admin/structure/webform/manage/contact/element/add/webform_test_offcanvas_width_element"][data-dialog-options*="800"]');
    $this->assertNoCssSelect('[href$="/admin/structure/webform/manage/contact/element/add/webform_test_offcanvas_width_element"][data-dialog-options*="550"]');

    // Create element.
    $this->drupalGet('/admin/structure/webform/manage/contact/element/add/webform_test_offcanvas_width_element');
    $edit = ['key' => 'webform_test_offcanvas_width_element'];
    $this->submitForm($edit, 'Save');

    // Check edit off-canvas element width is 800.
    $this->drupalGet('/admin/structure/webform/manage/contact');
    $this->assertCssSelect('[href$="/admin/structure/webform/manage/contact/element/webform_test_offcanvas_width_element/edit"][data-dialog-options*="800"]');
    $this->assertNoCssSelect('[href$="/admin/structure/webform/manage/contact/element/webform_test_offcanvas_width_element/edit"][data-dialog-options*="550"]');
  }

  /**
   * Tests permissions.
   */
  public function testPermissions() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('contact');

    // Check source page access not visible to user with 'administer webform'
    // permission.
    $account = $this->drupalCreateUser(['administer webform']);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/structure/webform/manage/' . $webform->id() . '/source');
    $assert_session->statusCodeEquals(403);
    $this->drupalLogout();

    // Check source page access not visible to user with 'edit webform source'
    // without 'administer webform' permission.
    $account = $this->drupalCreateUser(['edit webform source']);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/structure/webform/manage/' . $webform->id() . '/source');
    $assert_session->statusCodeEquals(403);
    $this->drupalLogout();

    // Check source page access visible to user with 'edit webform source'
    // and 'administer webform' permission.
    $account = $this->drupalCreateUser(['administer webform', 'edit webform source']);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/structure/webform/manage/' . $webform->id() . '/source');
    $assert_session->statusCodeEquals(200);
    $this->drupalLogout();
  }

}
