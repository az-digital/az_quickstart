<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform submission entity.
 *
 * @group webform
 */
class WebformSubmissionTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'webform', 'webform_test_submissions'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_submissions'];

  /**
   * Tests webform submission entity.
   */
  public function testWebformSubmission() {
    $normal_user = $this->drupalCreateUser();

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_submissions');

    /** @var \Drupal\webform\WebformSubmissionInterface[] $submissions */
    $submissions = array_values(\Drupal::entityTypeManager()->getStorage('webform_submission')->loadByProperties(['webform_id' => 'test_submissions']));

    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = reset($submissions);

    /* ********************************************************************** */

    // Check create submission.
    $this->assertInstanceOf(WebformSubmission::class, $webform_submission);

    // Check get webform.
    $this->assertEquals($webform_submission->getWebform()->id(), $webform->id());

    // Check that source entity is NULL.
    $this->assertNull($webform_submission->getSourceEntity());

    // Check getting source URL without uri, which will still return
    // the webform.
    $webform_submission
      ->set('uri', NULL)
      ->save();
    $this->assertEquals($webform_submission->getSourceUrl()->toString(), $webform->toUrl('canonical', ['absolute' => TRUE])->toString());

    // Check get source URL set to user 1.
    $webform_submission
      ->set('entity_type', 'user')
      ->set('entity_id', $normal_user->id())
      ->save();
    $this->assertEquals($webform_submission->getSourceUrl()->toString(), $normal_user->toUrl('canonical', ['absolute' => TRUE])->toString());

    // Check missing webform_id exception.
    try {
      WebformSubmission::create();
      $this->fail('Webform id (webform_id) is required to create a webform submission.');
    }
    catch (\Exception $exception) {
      // Webform id (webform_id) is required to create a webform submission.
    }

    // Check creating a submission with default data.
    $webform_submission = WebformSubmission::create(['webform_id' => $webform->id(), 'data' => ['custom' => 'value']]);
    $this->assertEquals($webform_submission->getData(), ['custom' => 'value']);

    // Check submission label.
    $webform_submission->save();
    $this->assertEquals($webform_submission->label(), $webform->label() . ': Submission #' . $webform_submission->serial());

    // Check test submission URI.
    // @see \Drupal\webform\WebformSubmissionForm::save
    $this->drupalLogin($this->rootUser);
    $sid = $this->postSubmissionTest($webform);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEquals($webform_submission->getSourceUrl()->toString(), $webform->toUrl('canonical', ['absolute' => TRUE])->toString());
    $this->drupalLogout();
  }

  /**
   * Tests duplicating webform submission.
   */
  public function testDuplicate() {
    $assert_session = $this->assertSession();

    $admin_submission_user = $this->drupalCreateUser([
      'administer webform submission',
    ]);

    /* ********************************************************************** */

    $this->drupalLogin($admin_submission_user);

    $webform = Webform::load('contact');
    $sid = $this->postSubmission($webform, [
      'subject' => '{Original Subject}',
      'message' => '{Original Message}',
    ]);
    $webform_submission = WebformSubmission::load($sid);

    // Check duplicate form title.
    $this->drupalGet("admin/structure/webform/manage/contact/submission/$sid/duplicate");
    $assert_session->responseContains('Duplicate Contact: Submission #' . $webform_submission->serial());

    // Duplicate submission.
    $this->drupalGet("admin/structure/webform/manage/contact/submission/$sid/duplicate");
    $this->submitForm(['subject' => '{Duplicate Subject}'], 'Send message');
    $duplicate_sid = $this->getLastSubmissionId($webform);
    /** @var \Drupal\webform\WebformSubmissionInterface $duplicate_submission */
    $duplicate_submission = WebformSubmission::load($duplicate_sid);

    // Check duplicate submission.
    $this->assertNotEquals($sid, $duplicate_sid);
    $this->assertEquals($duplicate_submission->getElementData('subject'), '{Duplicate Subject}');
    $this->assertEquals($duplicate_submission->getElementData('message'), '{Original Message}');
  }

}
