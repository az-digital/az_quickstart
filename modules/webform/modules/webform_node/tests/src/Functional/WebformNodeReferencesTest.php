<?php

namespace Drupal\Tests\webform_node\Functional;

/**
 * Tests for webform node references.
 *
 * @group webform_node
 */
class WebformNodeReferencesTest extends WebformNodeBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'help', 'webform', 'webform_node'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_variant_multiple'];

  /**
   * Tests webform node references.
   */
  public function testReferences() {
    global $base_path;

    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);
    $this->drupalPlaceBlock('help_block');

    // Check references tab's empty message.
    $this->drupalGet('/admin/structure/webform/manage/contact/references');
    $assert_session->responseContains('There are no webform node references.');
    $assert_session->linkExists('Add Webform');
    $assert_session->linkByHrefExists($base_path . 'node/add/webform?webform_id=contact');

    // Create webform node.
    $node = $this->drupalCreateNode(['type' => 'webform']);
    $node->webform->target_id = 'contact';
    $node->save();

    $this->drupalGet('/admin/structure/webform/manage/contact/references');

    // Check references tab does not include empty message.
    $assert_session->responseNotContains('There are no webform node references.');

    // Check references tabs includes webform node.
    $assert_session->linkExists($node->label());

    // Check references tab local actions.
    $assert_session->responseContains('<li><a href="' . $base_path . 'node/add/webform?webform_id=contact" class="button button-action" data-drupal-link-query="{&quot;webform_id&quot;:&quot;contact&quot;}" data-drupal-link-system-path="node/add/webform">Add Webform</a></li>');

    // Check node with prepopulated webform.
    $this->drupalGet('/node/add/webform', ['query' => ['webform_id' => 'contact']]);
    $assert_session->fieldValueEquals('webform[0][target_id]', 'contact');

    // Check node without prepopulated webform warning.
    $this->drupalGet('/node/add/webform');
    $assert_session->responseContains('Webforms must first be <a href="' . $base_path . 'admin/structure/webform">created</a> before referencing them.');

    // Check webform with variants.
    $this->drupalGet('/admin/structure/webform/manage/test_variant_multiple/references');
    $assert_session->linkByHrefNotExists($base_path . 'node/add/webform?webform_id=test_variant_multiple');
    $assert_session->linkExists('Add reference');
    $assert_session->linkByHrefExists($base_path . 'admin/structure/webform/manage/test_variant_multiple/references/add');

    // Check that add reference form redirects to the create content form.
    $this->drupalGet('/admin/structure/webform/manage/test_variant_multiple/references/add');
    $edit = [
      'bundle' => 'webform',
      'webform_title' => 'Testing 123',
      'webform_default_data[letter]' => 'a',
      'webform_default_data[number]' => '1',
    ];
    $this->submitForm($edit, 'Create content');
    $assert_session->fieldValueEquals('title[0][value]', 'Testing 123');
    $this->assertTrue($assert_session->optionExists('edit-webform-0-target-id', 'test_variant_multiple')->hasAttribute('selected'));
    $assert_session->responseContains('>letter: a
number: &#039;1&#039;
</textarea>');
  }

}
