<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\file\Entity\File;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Test for webform element managed file handling.
 *
 * @group webform
 */
class WebformElementManagedFileTest extends WebformElementManagedFileTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['file', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_element_managed_file',
    'test_element_managed_file_dis',
    'test_element_managed_file_name',
  ];

  /**
   * The 'test_element_managed_file' webform.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * Admin submission user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminSubmissionUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->webform = Webform::load('test_element_managed_file');

    $this->adminSubmissionUser = $this->drupalCreateUser([
      'administer webform submission',
    ]);
  }

  /**
   * Test single and multiple file upload.
   */
  public function testFileUpload() {
    $assert_session = $this->assertSession();

    /* Element rendering */

    $this->drupalGet('/webform/test_element_managed_file');

    // Check single file upload button.
    $assert_session->responseContains('<label for="edit-managed-file-single-button-upload-button--2" class="button button-action webform-file-button">Choose file</label>');

    // Check multiple file upload button.
    $assert_session->responseContains('<label for="edit-managed-file-multiple-button-upload-button--2" class="button button-action webform-file-button">Choose files</label>');

    // Check single custom file upload button.
    $assert_session->responseContains('<label style="color: red" for="edit-managed-file-single-button-custom-upload-button--2" class="button button-action webform-file-button">{Custom label}</label>');

    // Check comma delimited file extensions.
    $assert_session->responseContains('Allowed types: txt, text.');

    /* Element processing */

    $this->checkFileUpload('single', $this->files[0], $this->files[1]);
    $this->checkFileUpload('multiple', $this->files[2], $this->files[3]);

    /* Multiple processing */

    // Check file input is visible.
    $this->drupalGet('/webform/test_element_managed_file');
    $assert_session->fieldExists('files[managed_file_multiple_two][]');
    $assert_session->buttonExists('managed_file_multiple_two_upload_button');

    // phpcs:disable
    // Check that only two files can be uploaded.
    // @todo Determine how to submit multiple files.
    /*
    $edit = [
      'files[managed_file_multiple_two][]' => [
        \Drupal::service('file_system')->realpath($this->files[0]->uri),
        \Drupal::service('file_system')->realpath($this->files[1]->uri),
        \Drupal::service('file_system')->realpath($this->files[2]->uri),
      ],
    ];
    $this->drupalGet('/webform/test_element_managed_file');
    $this->submitForm($edit, 'Upload');
    $assert_session->responseContains('<em class="placeholder">managed_file_multiple_two</em> can only hold 2 values but there were 3 uploaded. The following files have been omitted as a result: <em class="placeholder">text-2.txt</em>.');

    // Check file input is removed.
    $assert_session->fieldNotExists('files[managed_file_multiple_two][]');
    $assert_session->fieldNotExists('managed_file_multiple_two_upload_button');
    */
    // phpcs:enable

    /* File placeholder */

    // Check placeholder is displayed.
    $this->drupalGet('/webform/test_element_managed_file');
    $assert_session->responseContains('<div class="webform-managed-file-placeholder managed-file-placeholder js-form-wrapper form-wrapper" data-drupal-selector="edit-managed-file-single-placeholder-file-placeholder" id="edit-managed-file-single-placeholder-file-placeholder">This is the single file upload placeholder</div>');
    $assert_session->responseContains('<div class="webform-managed-file-placeholder managed-file-placeholder js-form-wrapper form-wrapper" data-drupal-selector="edit-managed-file-multiple-placeholder-file-placeholder" id="edit-managed-file-multiple-placeholder-file-placeholder">This is the multiple file upload placeholder</div>');

    $this->drupalLogin($this->rootUser);

    // Check placeholder is not displayed when files are uploaded.
    $this->drupalGet('/webform/test_element_managed_file/test');
    $assert_session->responseNotContains('<div class="webform-managed-file-placeholder managed-file-placeholder js-form-wrapper form-wrapper" data-drupal-selector="edit-managed-file-single-placeholder-file-placeholder" id="edit-managed-file-single-placeholder-file-placeholder">This is the single file upload placeholder</div>');
    $assert_session->responseNotContains('<div class="webform-managed-file-placeholder managed-file-placeholder js-form-wrapper form-wrapper" data-drupal-selector="edit-managed-file-multiple-placeholder-file-placeholder" id="edit-managed-file-multiple-placeholder-file-placeholder">This is the multiple file upload placeholder</div>');

    $this->drupalLogout();

    /* Required error */

    // Set required error.
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_element_managed_file');
    $webform->setElementProperties('managed_file_single', $webform->getElementDecoded('managed_file_single') + [
      '#required' => TRUE,
      '#required_error' => '{Custom required error}',
    ]);
    $webform->save();

    // Check that required error is displayed.
    $this->postSubmission($webform);
    $assert_session->responseContains('<h2 class="visually-hidden">Error message</h2>');
    $assert_session->responseContains('{Custom required error}');
  }

  /**
   * Test the file renaming feature.
   *
   * The property #file_name_pattern is tested.
   */
  public function testFileRename() {
    $webform = Webform::load('test_element_managed_file_name');

    $source_for_filename = $this->randomMachineName();
    $sid = $this->postSubmission($webform, [
      'source_for_filename' => $source_for_filename,
      'files[file_single]' => \Drupal::service('file_system')->realpath($this->files[0]->uri),
      'files[file_multiple][]' => \Drupal::service('file_system')->realpath($this->files[0]->uri),
      'files[file_truncate]' => \Drupal::service('file_system')->realpath($this->files[0]->uri),
    ]);

    $this->drupalLogin($this->adminSubmissionUser);
    // Edit the submission and insert 1 extra file into the multiple element.
    $this->drupalGet('/webform/' . $webform->id() . '/submissions/' . $sid . '/edit');
    $edit = ['files[file_multiple][]' => \Drupal::service('file_system')->realpath($this->files[1]->uri)];
    $this->submitForm($edit, 'Save');

    $this->drupalLogout();

    /** @var \Drupal\webform\WebformSubmissionInterface $submission */
    $submission = WebformSubmission::load($sid);

    /** @var \Drupal\file\FileInterface $single_file */
    $single_file = File::load($submission->getElementData('file_single'));
    $this->assertEquals('file_single_' . $source_for_filename . '.txt', $single_file->getFilename());

    /** @var \Drupal\file\FileInterface[] $multiple_file */
    $multiple_file = File::loadMultiple($submission->getElementData('file_multiple'));
    $this->assertCount(2, $multiple_file, 'Two files found in the multiple element.');

    $i = -1;
    foreach ($multiple_file as $file) {
      $suffix = $i === -1 ? '' : '_' . $i;
      $this->assertEquals('file_multiple_' . $source_for_filename . $suffix . '.txt', $file->getFilename());
      $i++;
    }

    /** @var \Drupal\file\FileInterface $truncate_file */
    $truncate_file = File::load($submission->getElementData('file_truncate'));
    $this->assertEquals(strlen($truncate_file->getFileUri()), 250);
    $this->assertEquals('file_truncate_1234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901.txt', $truncate_file->getFilename());
  }

  /**
   * Test file management.
   */
  public function testFileManagement() {
    $this->drupalLogin($this->rootUser);

    $webform = Webform::load('test_element_managed_file');

    /* ********************************************************************** */
    // Test immediately delete file.
    /* ********************************************************************** */

    // Upload files.
    $sid = $this->postSubmissionTest($webform);
    /** @var \Drupal\webform\WebformSubmissionInterface $submission */
    $submission = WebformSubmission::load($sid);
    $managed_file_single = $this->fileLoad($submission->getElementData('managed_file_single'));

    // Check single file is not temporary.
    $this->debug($submission->getData());
    $this->assertNotNull($managed_file_single);
    $this->assertFalse($managed_file_single->isTemporary());

    // Check deleting file completely deletes the file record.
    $submission->delete();
    $managed_file_single = $this->fileLoad($submission->getElementData('managed_file_single'));
    $this->assertNull($managed_file_single);

    /* ********************************************************************** */
    // Test disabling immediately deleted temporary managed files.
    /* ********************************************************************** */

    // Disable deleting of temporary files.
    $config = \Drupal::configFactory()->getEditable('webform.settings');
    $config->set('file.delete_temporary_managed_files', FALSE);
    $config->save();

    // Upload files.
    $sid = $this->postSubmissionTest($webform);
    $submission = WebformSubmission::load($sid);

    // Check deleting file completely deletes the file record.
    $submission->delete();
    $managed_file_single = $this->fileLoad($submission->getElementData('managed_file_single'));
    $this->assertNotNull($managed_file_single);
    $this->assertTrue($managed_file_single->isTemporary());

    /* ********************************************************************** */
    // Test disabling unused files marked temporary.
    /* ********************************************************************** */

    // Disable deleting of temporary files.
    $config = \Drupal::configFactory()->getEditable('webform.settings');
    $config->set('file.make_unused_managed_files_temporary', FALSE);
    $config->save();

    // Upload files.
    $sid = $this->postSubmissionTest($webform);
    $submission = WebformSubmission::load($sid);

    // Check deleting file completely deletes the file record.
    $submission->delete();
    $managed_file_single = $this->fileLoad($submission->getElementData('managed_file_single'));
    $this->assertNotNull($managed_file_single);
    $this->assertFalse($managed_file_single->isTemporary());
  }

  /**
   * Test file upload with disabled results.
   */
  public function testFileUploadWithDisabledResults() {
    $this->drupalLogin($this->rootUser);

    $webform = Webform::load('test_element_managed_file_dis');

    // Upload new file.
    $sid = $this->postSubmissionTest($webform);
    $file = File::load($this->getLastFileId());

    // Check that no submission was saved to the database.
    $this->assertNull($sid);

    // Check file URI.
    $this->assertEquals($file->getFileUri(), 'private://webform/test_element_managed_file_dis/_sid_/managed_file.txt');

    // Check file is temporary.
    $this->assertTrue($file->isTemporary());

    // Check file_managed table has 1 record.
    $this->assertEquals(1, \Drupal::database()->query('SELECT COUNT(fid) AS total FROM {file_managed}')->fetchField());

    // Check file_usage table has no records.
    $this->assertEquals(0, \Drupal::database()->query('SELECT COUNT(fid) AS total FROM {file_usage}')->fetchField());
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
    $assert_session = $this->assertSession();

    $key = 'managed_file_' . $type;
    $parameter_name = ($type === 'multiple') ? "files[$key][]" : "files[$key]";

    $edit = [
      $parameter_name => \Drupal::service('file_system')->realpath($first_file->uri),
    ];
    $sid = $this->postSubmission($this->webform, $edit);

    /** @var \Drupal\webform\WebformSubmissionInterface $submission */
    $submission = WebformSubmission::load($sid);

    /** @var \Drupal\file\FileInterface $file */
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
    if ($type === 'multiple') {
      $assert_session->responseContains('<label>managed_file_multiple</label>');
      $assert_session->responseContains('<ul>');
    }
    $assert_session->responseContains('<span class="file file--mime-text-plain file--text"><a href="' . $file->createFileUrl() . '" type="text/plain">' . $file->getFilename() . '</a></span>');

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

    /** @var \Drupal\file\FileInterface $test_file_0 */
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

    // Check that file directory was create.
    $this->assertFileExists('private://webform/test_element_managed_file/' . $sid . '/');

    // Delete the submission.
    $submission->delete();

    // Check that test file 1 was deleted from the disk and database.
    $this->assertFileDoesNotExist($new_file->getFileUri(), 'Test new file deleted from disk');
    $this->assertEquals(0, \Drupal::database()->query('SELECT COUNT(fid) AS total FROM {file_managed} WHERE fid = :fid', [':fid' => $new_fid])->fetchField(), 'Test new file deleted from database');

    // Check that empty file directory was deleted.
    $this->assertFileDoesNotExist('private://webform/test_element_managed_file/' . $sid . '/');
  }

}
