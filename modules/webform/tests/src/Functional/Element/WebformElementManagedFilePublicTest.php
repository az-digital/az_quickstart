<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Test for webform element managed public file handling (DRUPAL-PSA-2016-003).
 *
 * @see https://www.drupal.org/psa-2016-003
 *
 * @group webform
 */
class WebformElementManagedFilePublicTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_managed_file'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['file', 'webform', 'webform_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set public file upload support for testing.
    $settings_config = \Drupal::configFactory()->getEditable('webform.settings');
    $settings_config->set('file.file_public', TRUE);
    $settings_config->save();
  }

  /**
   * Test public upload protection.
   */
  public function testPublicUpload() {
    $assert_session = $this->assertSession();

    // Check status report private file system warning.
    $requirements = webform_requirements('runtime');
    $this->assertEquals($requirements['webform_file_private']['value'], (string) 'Private file system is set.');

    $this->drupalLogin($this->rootUser);

    // Check element webform warning message for public files.
    $this->drupalGet('/admin/structure/webform/manage/test_element_managed_file/element/managed_file_single/edit');
    $assert_session->responseContains('Public files upload destination is dangerous for webforms that are available to anonymous and/or untrusted users.');
    $assert_session->fieldExists('edit-properties-uri-scheme-public');

    // Check element webform warning message not visible public files.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('file.file_public', FALSE)
      ->save();
    $this->drupalGet('/admin/structure/webform/manage/test_element_managed_file/element/managed_file_single/edit');
    $assert_session->responseNotContains('Public files upload destination is dangerous for webforms that are available to anonymous and/or untrusted users.');
    $assert_session->fieldNotExists('edit-properties-uri-scheme-public');

    /* ********************************************************************** */
    // NOTE: Unable to test private file upload warning because SimpleTest
    // automatically enables private file uploads.
    /* ********************************************************************** */

    // Check managed_file element is enabled.
    $this->drupalGet('/admin/structure/webform/manage/test_element_managed_file/element/add');
    $assert_session->responseContains('>File<');

    // Disable managed file element.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('element.excluded_elements.managed_file', 'managed_file')
      ->save();

    // Check disabled managed_file element remove from add element dialog.
    $this->drupalGet('/admin/structure/webform/manage/test_element_managed_file/element/add');
    $assert_session->responseNotContains('>File<');

    // Check disabled managed_file element warning.
    $this->drupalGet('/admin/structure/webform/manage/test_element_managed_file');
    $assert_session->responseContains('<em class="placeholder">managed_file_single</em> is a <em class="placeholder">File</em> element, which has been disabled and will not be rendered.');
    $assert_session->responseContains('<em class="placeholder">managed_file_multiple</em> is a <em class="placeholder">File</em> element, which has been disabled and will not be rendered.');
  }

}
