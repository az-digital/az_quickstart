<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Test for webform element managed file preview.
 *
 * @group webform
 */
class WebformElementManagedFilePreviewTest extends WebformElementManagedFileTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['file', 'image', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_managed_file_prev'];

  /**
   * Test image file upload.
   */
  public function testImageFileUpload() {
    global $base_url;

    $assert_session = $this->assertSession();

    // Check that anonymous users can not preview files.
    $this->drupalGet('/webform/test_element_managed_file_prev/test');
    $assert_session->responseContains('<span data-drupal-selector="edit-webform-image-file-file-1-filename" class="file file--mime-image-gif file--image">webform_image_file.gif</span>');
    $assert_session->responseContains('<span data-drupal-selector="edit-webform-audio-file-file-3-filename" class="file file--mime-audio-mpeg file--audio">webform_audio_file.mp3</span>');
    $assert_session->responseContains('<span data-drupal-selector="edit-webform-video-file-file-5-filename" class="file file--mime-video-mp4 file--video">webform_video_file.mp4</span>');
    $assert_session->responseContains('<span data-drupal-selector="edit-webform-document-file-file-7-filename" class="file file--mime-text-plain file--text">webform_document_file.txt</span>');

    // Login admin user.
    $this->drupalLogin($this->rootUser);

    // Check that authenticated users can preview files.
    $this->drupalGet('/webform/test_element_managed_file_prev/test');

    $assert_session->responseContains('<div class="webform-managed-file-preview webform-image-file-preview js-form-wrapper form-wrapper" data-drupal-selector="edit-webform-image-file-file-9-filename" id="edit-webform-image-file-file-9-filename">');
    $assert_session->responseContains('<a href="' . $base_url . '/system/files/webform/test_element_managed_file_prev/_sid_/webform_image_file_0.gif" class="js-webform-image-file-modal webform-image-file-modal">');

    $assert_session->responseContains('<div class="webform-managed-file-preview webform-audio-file-preview js-form-wrapper form-wrapper" data-drupal-selector="edit-webform-audio-file-file-11-filename" id="edit-webform-audio-file-file-11-filename">');
    $assert_session->responseContains('<source src="' . $base_url . '/system/files/webform/test_element_managed_file_prev/_sid_/webform_audio_file_0.mp3" type="audio/mpeg">');

    $assert_session->responseContains('<div class="webform-managed-file-preview webform-video-file-preview js-form-wrapper form-wrapper" data-drupal-selector="edit-webform-video-file-file-13-filename" id="edit-webform-video-file-file-13-filename">');
    $assert_session->responseContains('<source src="' . $base_url . '/system/files/webform/test_element_managed_file_prev/_sid_/webform_video_file_0.mp4" type="video/mp4">');

    $assert_session->responseContains('<div class="webform-managed-file-preview webform-document-file-preview js-form-wrapper form-wrapper" data-drupal-selector="edit-webform-document-file-file-15-filename" id="edit-webform-document-file-file-15-filename">');
    $assert_session->responseContains($base_url . '/system/files/webform/test_element_managed_file_prev/_sid_/webform_document_file_0.txt');
  }

}
