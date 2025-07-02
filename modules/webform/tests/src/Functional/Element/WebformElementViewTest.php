<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for view element.
 *
 * @group webform
 */
class WebformElementViewTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_ui', 'views', 'views_ui'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_view'];

  /**
   * Test view element.
   */
  public function testView() {
    $assert_session = $this->assertSession();

    // Check that embedded view is render.
    $this->drupalGet('/webform/test_element_view');
    $assert_session->responseContains('No submissions available.');

    // Check that embedded view can't be edited.
    $admin_webform_account = $this->drupalCreateUser(['administer webform']);
    $this->drupalLogin($admin_webform_account);
    $this->drupalGet('/admin/structure/webform/manage/test_element_view/element/view/edit');
    $assert_session->responseContains("Only users who can 'Administer views' or 'Edit webform source code' can update the view name, display id, and arguments.");
    $assert_session->fieldNotExists('properties[name]');
    $assert_session->fieldNotExists('properties[display_id]');

    // Check that embedded view can be edited.
    $admin_views_account = $this->drupalCreateUser(['administer webform', 'administer views']);
    $this->drupalLogin($admin_views_account);
    $this->drupalGet('/admin/structure/webform/manage/test_element_view/element/view/edit');
    $assert_session->responseNotContains("Only users who can 'Administer views' or 'Edit webform source code' can update the view name, display id, and arguments.");
    $assert_session->fieldExists('properties[name]');
    $assert_session->fieldExists('properties[display_id]');

    // Check view name validation.
    $this->drupalGet('/admin/structure/webform/manage/test_element_view/element/view/edit');
    $edit = ['properties[name]' => 'xxx'];
    $this->submitForm($edit, 'Save');
    $assert_session->responseContains('View <em class="placeholder">xxx</em> does not exist.');

    // Check view display id validation.
    $this->drupalGet('/admin/structure/webform/manage/test_element_view/element/view/edit');
    $edit = ['properties[display_id]' => 'xxx'];
    $this->submitForm($edit, 'Save');
    $assert_session->responseContains('View display <em class="placeholder">xxx</em> does not exist.');

    // Check view exposed filter validation.
    $this->drupalGet('/admin/structure/webform/manage/test_element_view/element/view/edit');
    $edit = ['properties[display_id]' => 'embed_administer'];
    $this->submitForm($edit, 'Save');
    $assert_session->responseContains('View display <em class="placeholder">embed_administer</em> has exposed filters which will break the webform.');

    // Check view exposed filter validation.
    $this->drupalGet('/admin/structure/webform/manage/test_element_view/element/view/edit');
    $edit = [
      'properties[display_id]' => 'embed_administer',
      'properties[display_on]' => 'view',
    ];
    $this->submitForm($edit, 'Save');
    $assert_session->responseNotContains('View display <em class="placeholder">embed_administer</em> has exposed filters which will break the webform.');
  }

}
