<?php

namespace Drupal\Tests\webform\Functional\Handler;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Tests for action webform handler functionality.
 *
 * @group webform
 */
class WebformHandlerActionTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_handler_action'];

  /**
   * Test action handler.
   */
  public function testActionHandler() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_handler_action');

    // Create submission.
    $sid = $this->postSubmission($webform);

    $webform_submission = WebformSubmission::load($sid);

    // Check that submission is not flagged (sticky).
    $this->assertFalse($webform_submission->isSticky());

    // Check that submission is unlocked.
    $this->assertFalse($webform_submission->isLocked());

    // Check that submission notes is empty.
    $this->assertEmpty($webform_submission->getNotes());

    // Check that last note is empty.
    $this->assertEmpty($webform_submission->getElementData('notes_add'));

    // Flag and add new note to the submission.
    $this->drupalGet("admin/structure/webform/manage/test_handler_action/submission/$sid/edit");
    $edit = [
      'sticky' => 'flag',
      'notes_add' => 'This is the first note',
    ];
    $this->submitForm($edit, 'Save');

    // Check messages.
    $assert_session->responseContains('Submission has been flagged.');
    $assert_session->responseContains('Submission notes have been updated.');

    // Reload the webform submission.
    \Drupal::entityTypeManager()->getStorage('webform_submission')->resetCache();
    $webform_submission = WebformSubmission::load($sid);

    // Check that sticky is set.
    $this->assertTrue($webform_submission->isSticky());

    // Change that notes_add is empty.
    $this->assertEmpty($webform_submission->getElementData('notes_add'));

    // Check that notes_last is updated.
    $this->assertEquals($webform_submission->getElementData('notes_last'), 'This is the first note');

    // Unflag and add new note to the submission.
    $this->drupalGet("admin/structure/webform/manage/test_handler_action/submission/$sid/edit");
    $edit = [
      'sticky' => 'unflag',
      'notes_add' => 'This is the second note',
    ];
    $this->submitForm($edit, 'Save');

    // Check messages.
    $assert_session->responseContains('Submission has been unflagged.');
    // $assert_session->responseContains('Submission has been unlocked.');
    $assert_session->responseContains('Submission notes have been updated.');

    // Reload the webform submission.
    \Drupal::entityTypeManager()->getStorage('webform_submission')->resetCache();
    $webform_submission = WebformSubmission::load($sid);

    // Check that sticky is unset.
    $this->assertFalse($webform_submission->isSticky());

    // Change that notes_add is empty.
    $this->assertEmpty($webform_submission->getElementData('notes_add'));

    // Check that notes updated.
    $this->assertEquals($webform_submission->getNotes(), 'This is the first note' . PHP_EOL . PHP_EOL . 'This is the second note');

    // Check that notes_last is updated with second note.
    $this->assertEquals($webform_submission->getElementData('notes_last'), 'This is the second note');

    // Lock submission.
    $this->drupalGet("admin/structure/webform/manage/test_handler_action/submission/$sid/edit");
    $edit = ['lock' => 'locked'];
    $this->submitForm($edit, 'Save');

    // Check locked message.
    $assert_session->responseContains('Submission has been locked.');

    // Reload the webform submission.
    \Drupal::entityTypeManager()->getStorage('webform_submission')->resetCache();
    $webform_submission = WebformSubmission::load($sid);

    // Check that submission is locked.
    $this->assertTrue($webform_submission->isLocked());
    $this->assertEquals(WebformSubmissionInterface::STATE_LOCKED, $webform_submission->getState());

    // Check that submission is locked.
    $this->drupalGet("admin/structure/webform/manage/test_handler_action/submission/$sid/edit");
    $assert_session->responseContains('This is submission was automatically locked.');

    // Programmatically unlock the submission.
    $webform_submission->setElementData('lock', 'unlocked');
    $webform_submission->save();

    $this->assertFalse($webform_submission->isLocked());
    $this->assertNotEquals(WebformSubmissionInterface::STATE_LOCKED, $webform_submission->getState());
  }

}
