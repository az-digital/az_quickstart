<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for confidential webform submissions.
 *
 * @group webform
 */
class WebformSettingsConfidentialTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_confidential'];

  /**
   * Tests webform confidential setting.
   */
  public function testConfidential() {
    $assert_session = $this->assertSession();

    /** @var \Drupal\user\RoleInterface $anonymous_role */
    $anonymous_role = Role::load('anonymous');
    $anonymous_role->grantPermission('view own webform submission')
      ->grantPermission('edit own webform submission')
      ->grantPermission('delete own webform submission')
      ->save();

    /* ********************************************************************** */

    $this->drupalLogin($this->rootUser);

    $webform = Webform::load('test_form_confidential');

    // Check logout warning when accessing webform.
    $this->drupalGet('/webform/test_form_confidential');
    $assert_session->fieldNotExists('edit-name');
    $assert_session->responseContains('This form is confidential.');

    // Check no logout warning when testing webform.
    $this->drupalGet('/webform/test_form_confidential/test');
    $assert_session->fieldExists('edit-name');
    $assert_session->responseNotContains('This form is confidential.');

    // Check that test submission does not record the IP address.
    $sid = $this->postSubmissionTest($webform, ['name' => 'John']);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEquals($webform_submission->getRemoteAddr(), '(unknown)');
    $this->assertEquals($webform_submission->getOwnerId(), 0);

    // Check anonymous access to webform.
    $this->drupalLogout();
    $this->drupalGet('/webform/test_form_confidential');
    $assert_session->fieldExists('edit-name');
    $assert_session->responseNotContains('This form is confidential.');

    // Check that submission does not track the requests IP address.
    $sid = $this->postSubmission($webform, ['name' => 'John']);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEquals($webform_submission->getRemoteAddr(), '(unknown)');
    $this->assertEquals($webform_submission->getOwnerId(), 0);

    // Check that previous submissions are visible.
    $this->drupalGet('/webform/test_form_confidential');
    $assert_session->responseContains('View your previous submission');

    // Check that anonymous submission is not converted to authenticated.
    // @see \Drupal\webform\WebformSubmissionStorage::userLogin
    $this->drupalLogin($this->rootUser);
    $webform_submission = $this->loadSubmission($sid);
    $this->assertEquals($webform_submission->getOwnerId(), 0);

    // Check that previous submissions $_SESSION was unset after login/logout.
    $this->drupalLogout();
    $this->drupalGet('/webform/test_form_confidential');
    $assert_session->responseNotContains('View your previous submission.');
  }

}
