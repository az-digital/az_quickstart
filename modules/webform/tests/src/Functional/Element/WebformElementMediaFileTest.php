<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\file\Entity\File;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Test for webform element managed file handling.
 *
 * @group webform
 */
class WebformElementMediaFileTest extends WebformElementManagedFileTestBase {

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
  protected static $testWebforms = ['test_element_media_file'];

  /**
   * Test media file upload elements.
   */
  public function testMediaFileUpload() {
    $assert_session = $this->assertSession();

    /* Element render */

    // Get test webform.
    $this->drupalGet('/webform/test_element_media_file');

    // Check document file.
    $assert_session->responseContains('<input aria-describedby="edit-document-file--description" data-drupal-selector="edit-document-file-upload" type="file" id="edit-document-file-upload" name="files[document_file]" size="22" class="js-form-file form-file" />');

    // Check audio file.
    $assert_session->responseContains('<input aria-describedby="edit-audio-file--description" data-drupal-selector="edit-audio-file-upload" accept="audio/*" type="file" id="edit-audio-file-upload" name="files[audio_file]" size="22" class="js-form-file form-file" />');

    // Check image file.
    $assert_session->responseContains('<input aria-describedby="edit-image-file--description" data-drupal-selector="edit-image-file-upload" accept="image/*" type="file" id="edit-image-file-upload" name="files[image_file]" size="22" class="js-form-file form-file" />');

    // Check video file.
    $assert_session->responseContains('<input aria-describedby="edit-video-file--description" data-drupal-selector="edit-video-file-upload" accept="video/mp4,video/x-m4v,video/*" type="file" id="edit-video-file-upload" name="files[video_file]" size="22" class="js-form-file form-file" />');

    /* Element processing */

    // Get test webform preview with test values.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/webform/test_element_media_file/test');
    $this->submitForm([], 'Preview');

    // Check audio file preview.
    $assert_session->responseContains('<source src="' . $this->getAbsoluteUrl('/system/files/webform/test_element_media_file/_sid_/audio_file_mp3.mp3') . '" type="audio/mpeg">');

    // Check image file preview.
    $assert_session->responseContains('<img class="webform-image-file" alt="image_file_jpg.jpg" title="image_file_jpg.jpg" src="' . $this->getAbsoluteUrl('/system/files/webform/test_element_media_file/_sid_/image_file_jpg.jpg') . '" />');

    // Check image file link to modal.
    $assert_session->responseContains('/system/files/webform/test_element_media_file/_sid_/image_file_jpg_modal.jpg" class="js-webform-image-file-modal webform-image-file-modal">');
    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.3',
      currentCallable: fn() => $assert_session->responseContains('/system/files/styles/thumbnail/private/webform/test_element_media_file/_sid_/image_file_jpg_modal.jpg.webp?itok='),
      deprecatedCallable: fn() => $assert_session->responseContains('/system/files/styles/thumbnail/private/webform/test_element_media_file/_sid_/image_file_jpg_modal.jpg?itok='),
    );

    // Check video file preview.
    $assert_session->responseContains('<source src="' . $this->getAbsoluteUrl('/system/files/webform/test_element_media_file/_sid_/video_file_mp4.mp4') . '" type="video/mp4">');
  }

  /* ************************************************************************ */
  // Helper functions.
  // @see \Drupal\file\Tests\FileFieldTestBase::getTestFile
  /* ************************************************************************ */

  /**
   * Check file upload.
   *
   * @param string $type
   *   The type of file upload which can be either single or multiple.
   * @param object $first_file
   *   The first file to be uploaded.
   * @param object $second_file
   *   The second file that replaces the first file.
   */
  protected function checkFileUpload($type, $first_file, $second_file) {
    $key = 'managed_file_' . $type;
    $parameter_name = ($type === 'multiple') ? "files[$key][]" : "files[$key]";

    $edit = [
      $parameter_name => \Drupal::service('file_system')->realpath($first_file->uri),
    ];
    $sid = $this->postSubmission($this->webform, $edit);

    /** @var \Drupal\webform\WebformSubmissionInterface $submission */
    $submission = WebformSubmission::load($sid);

    /** @var \Drupal\file\Entity\File $file */
    $fid = $this->getLastFileId();
    $file = File::load($fid);

    // Check that test file was uploaded to the current submission.
    $second = ($type === 'multiple') ? [$fid] : $fid;
    $this->assertEquals($submission->getElementData($key), $second, 'Test file was upload to the current submission');

    // Check test file file usage.
    $this->assertSame(['webform' => ['webform_submission' => [$sid => '1']]], $this->fileUsage->listUsage($file), 'The file has 1 usage.');

    // Check test file uploaded file path.
    $this->assertEquals($file->getFileUri(), 'private://webform/test_element_managed_file/' . $sid . '/' . $first_file->filename);

    // Check that test file exists.
    $this->assertFileExists($file->getFileUri());

    // Login admin user.
    $this->drupalLogin($this->adminSubmissionUser);

    // Check managed file formatting.
    $this->drupalGet('/admin/structure/webform/manage/test_element_managed_file/submission/' . $sid);
    $assert_session = $this->assertSession();
    if ($type === 'multiple') {
      $assert_session->responseContains('<label>managed_file_multiple</label>');
      $assert_session->responseContains('<div class="item-list">');
    }
    $assert_session->responseContains('<span class="file file--mime-text-plain file--text"> <a href="' . $file->createFileUrl(FALSE) . '" type="text/plain; length=' . $file->getSize() . '">' . $file->getFilename() . '</a></span>');

    // Remove the uploaded file.
    $this->drupalGet('/admin/structure/webform/manage/test_element_managed_file/submission/' . $sid . '/edit');
    if ($type === 'multiple') {
      $edit = ['managed_file_multiple[file_' . $fid . '][selected]' => TRUE];
      $submit = 'Remove selected';
    }
    else {
      $edit = [];
      $submit = 'Remove';
    }
    $this->submitForm($edit, $submit);

    // Upload new file.
    $edit = [
      $parameter_name => \Drupal::service('file_system')->realpath($second_file->uri),
    ];
    $this->submitForm($edit, 'Upload');

    // Submit the new file.
    $this->submitForm([], 'Save');

    /** @var \Drupal\file\Entity\File $test_file_0 */
    $new_fid = $this->getLastFileId();
    $new_file = File::load($new_fid);

    \Drupal::entityTypeManager()->getStorage('webform_submission')->resetCache();
    $submission = WebformSubmission::load($sid);

    // Check that test new file was uploaded to the current submission.
    $second = ($type === 'multiple') ? [$new_fid] : $new_fid;
    $this->assertEquals($submission->getElementData($key), $second, 'Test new file was upload to the current submission');

    // Check that test file was deleted from the disk and database.
    $this->assertFileDoesNotExist($file->getFileUri(), 'Test file deleted from disk');
    $this->assertEquals(0, \Drupal::database()->query('SELECT COUNT(fid) AS total FROM {file_managed} WHERE fid = :fid', [':fid' => $fid])->fetchField(), 'Test file 0 deleted from database');
    $this->assertEquals(0, \Drupal::database()->query('SELECT COUNT(fid) AS total FROM {file_usage} WHERE fid = :fid', [':fid' => $fid])->fetchField(), 'Test file 0 deleted from database');

    // Check test file 1 file usage.
    $this->assertSame(['webform' => ['webform_submission' => [$sid => '1']]], $this->fileUsage->listUsage($new_file), 'The new file has 1 usage.');

    // Delete the submission.
    $submission->delete();

    // Check that test file 1 was deleted from the disk and database.
    $this->assertFileDoesNotExist($new_file->getFileUri(), 'Test new file deleted from disk');
    $this->assertEquals(0, \Drupal::database()->query('SELECT COUNT(fid) AS total FROM {file_managed} WHERE fid = :fid', [':fid' => $new_fid])->fetchField(), 'Test new file deleted from database');
  }

}
