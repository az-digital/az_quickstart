<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Test for webform element managed file handling.
 *
 * @group webform
 */
class WebformElementManagedFilePrivateTest extends WebformElementManagedFileTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_managed_file'];

  /**
   * Test private files.
   */
  public function testPrivateFiles() {
    $assert_session = $this->assertSession();

    $admin_submission_user = $this->drupalCreateUser([
      'administer webform submission',
    ]);

    $webform = Webform::load('test_element_managed_file');

    /* ********************************************************************** */

    $elements = $webform->getElementsDecoded();
    $elements['managed_file_single']['#uri_scheme'] = 'private';
    $webform->setElements($elements);
    $webform->save();

    $this->drupalLogin($admin_submission_user);

    // Upload private file as authenticated user.
    $edit = [
      'files[managed_file_single]' => \Drupal::service('file_system')->realpath($this->files[0]->uri),
    ];
    $sid = $this->postSubmission($webform, $edit);

    /** @var \Drupal\webform\WebformSubmissionInterface $submission */
    $submission = WebformSubmission::load($sid);

    /** @var \Drupal\file\Entity\File $file */
    $fid = $this->getLastFileId();
    $file = File::load($fid);

    // Check that test file 3 was uploaded to the current submission.
    $this->assertEquals($submission->getElementData('managed_file_single'), $fid, 'Test file 3 was upload to the current submission');

    // Check test file 3 file usage.
    $this->assertSame(['webform' => ['webform_submission' => [$sid => '1']]], $this->fileUsage->listUsage($file), 'The file has 3 usage.');

    // Check test file 3 uploaded file path.
    $this->assertEquals($file->getFileUri(), 'private://webform/test_element_managed_file/' . $sid . '/' . $this->files[0]->filename);

    // Check private file access allowed.
    $this->drupalGet($file->createFileUrl(FALSE));
    $assert_session->statusCodeEquals(200);

    $this->drupalLogout();

    // Check private file access redirects to user login page with destination.
    $this->drupalGet($file->createFileUrl(FALSE));
    $assert_session->statusCodeEquals(200);

    $destination_url = Url::fromUri('base://system/files', [
      'query' => [
        'file' => 'webform/test_element_managed_file/' . $sid . '/' . $this->files[0]->filename,
      ],
    ]);
    $assert_session->addressEquals(Url::fromRoute('user.login', [], [
      'query' => [
        'destination' => $destination_url->toString(),
      ],
    ]));

    // Upload private file and preview as anonymous user.
    $this->drupalGet('/webform/' . $webform->id());
    $edit = [
      'files[managed_file_single]' => \Drupal::service('file_system')->realpath($this->files[1]->uri),
    ];
    $this->submitForm($edit, 'Preview');

    /** @var \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator */
    $file_url_generator = \Drupal::service('file_url_generator');

    $temp_file_uri = $file_url_generator->generateAbsoluteString('private://webform/test_element_managed_file/_sid_/' . basename($this->files[1]->uri));

    // Check that temp file is not linked.
    $assert_session->responseNotContains('<span class="file file--mime-text-plain file--text"><a href="' . $temp_file_uri . '" type="text/plain; length=16384">text-1.txt</a></span>');
    $assert_session->responseContains('<span class="file file--mime-text-plain file--text">' . basename($this->files[1]->uri) . '</span>');

    // Check that anonymous user can't access temp file.
    $this->drupalGet($temp_file_uri);
    $assert_session->statusCodeEquals(403);

    // Check that authenticated user can't access temp file.
    $this->drupalLogin($admin_submission_user);
    $this->drupalGet($temp_file_uri);
    $assert_session->statusCodeEquals(403);

    // Disable redirect anonymous users to login when attempting to access
    // private file uploads.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('file.file_private_redirect', FALSE)
      ->save();

    // Check private file access redirects to user login page with destination.
    $this->drupalLogout();
    $this->drupalGet($file->createFileUrl(FALSE));
    $assert_session->statusCodeEquals(403);
    $assert_session->addressEquals('system/files/webform/test_element_managed_file/' . $sid . '/' . $this->files[0]->filename);
  }

}
