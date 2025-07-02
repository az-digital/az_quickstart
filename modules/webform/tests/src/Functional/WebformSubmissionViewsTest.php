<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform submission views integration.
 *
 * @group webform
 */
class WebformSubmissionViewsTest extends WebformBrowserTestBase {

  /**
   * Set default theme to starterkit to have view specific classes.
   *
   * @var string
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['views', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_submission_views'];

  /**
   * Tests submissions views.
   */
  public function testSubmissionViewsAccess() {
    $assert_session = $this->assertSession();

    // Check administer view.
    $user = $this->drupalCreateUser(['administer webform submission']);
    $this->drupalLogin($user);
    $this->drupalGet('/admin/structure/webform/manage/test_submission_views/results/submissions');
    $assert_session->responseContains('view-id-webform_submissions view-display-id-embed_administer');

    // Check 200 response.
    $this->drupalGet('/admin/structure/webform/manage/test_submission_views/results/submissions/admin');
    $assert_session->statusCodeEquals(200);

    // Check manage view.
    $user = $this->drupalCreateUser(['edit any webform submission', 'view any webform submission']);
    $this->drupalLogin($user);
    $this->drupalGet('/admin/structure/webform/manage/test_submission_views/results/submissions');
    $assert_session->responseContains('view-id-webform_submissions view-display-id-embed_manage');

    // Check 403 access denied response.
    $this->drupalGet('/admin/structure/webform/manage/test_submission_views/results/submissions/admin');
    $assert_session->statusCodeEquals(403);

    // Check 404 not found response.
    $this->drupalGet('/admin/structure/webform/manage/test_submission_views/results/submissions/not_found');
    $assert_session->statusCodeEquals(404);

    // Check review view.
    $user = $this->drupalCreateUser(['view any webform submission']);
    $this->drupalLogin($user);
    $this->drupalGet('/admin/structure/webform/manage/test_submission_views/results/submissions');
    $assert_session->responseContains('view-id-webform_submissions view-display-id-embed_review');
  }

  /**
   * Tests submissions views.
   */
  public function testSubmissionViews() {
    $assert_session = $this->assertSession();

    $uid = $this->rootUser->id();
    $this->drupalLogin($this->rootUser);

    /* ********************************************************************** */
    // Global.
    /* ********************************************************************** */

    // Setup global submissions and user submissions views.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_submission_views', [
        'global' => [
          'view' => 'webform_submissions:embed_default',
          'title' => 'Global submissions',
          'global_routes' => ['entity.webform_submission.collection'],
          'webform_routes' => [],
          'node_routes' => [],
        ],
        'user' => [
          'view' => 'webform_submissions:embed_default',
          'title' => 'User submissions',
          'global_routes' => ['entity.webform_submission.user'],
          'webform_routes' => [],
          'node_routes' => [],
        ],
      ])
      ->save();

    // Check global submissions entity list is replaced by the view.
    $this->drupalGet('/admin/structure/webform/submissions/manage');
    $assert_session->responseNotContains('webform-results-table');
    $assert_session->responseContains('view-id-webform_submissions view-display-id-embed_default');

    // Check user submissions entity list is replaced by the view.
    $this->drupalGet("/user/$uid/submissions");
    $assert_session->responseNotContains('webform-results-table');
    $assert_session->responseContains('view-id-webform_submissions view-display-id-embed_default');

    // Clear global submission views replace.
    \Drupal::configFactory()
      ->getEditable('webform.settings')
      ->set('settings.default_submission_views_replace.global_routes', [])
      ->save();

    // Check global submissions entity list is displayed.
    $this->drupalGet('/admin/structure/webform/submissions/manage');
    $assert_session->responseContains('webform-results-table');
    $assert_session->responseNotContains('view-id-webform_submissions view-display-id-embed_default');

    // Check global submissions views is moved to dedicated path.
    $this->clickLink('Global submissions');
    $assert_session->addressEquals('/admin/structure/webform/submissions/manage/global');
    $assert_session->responseNotContains('webform-results-table');
    $assert_session->responseContains('view-id-webform_submissions view-display-id-embed_default');

    // Check user submissions entity list is displayed.
    $this->drupalGet("/user/$uid/submissions");
    $assert_session->responseContains('webform-results-table');
    $assert_session->responseNotContains('view-id-webform_submissions view-display-id-embed_default');

    // Check user submissions entity list is moved to dedicated path.
    $this->clickLink('User submissions');
    $assert_session->addressEquals("/user/$uid/submissions/user");
    $assert_session->responseNotContains('webform-results-table');
    $assert_session->responseContains('view-id-webform_submissions view-display-id-embed_default');

    /* ********************************************************************** */
    // Webform.
    /* ********************************************************************** */

    // Post a submission and save a draft.
    $webform = Webform::load('test_submission_views');
    $this->postSubmission($webform);
    $this->postSubmission($webform);
    $this->postSubmission($webform, [], 'Save Draft');
    $this->postSubmission($webform, [], 'Save Draft');

    // Check webform submissions views.
    $this->drupalGet('/admin/structure/webform/manage/test_submission_views/results/submissions');
    $assert_session->responseNotContains('webform-results-table');
    $assert_session->responseContains('view-id-webform_submissions view-display-id-embed_administer');

    // Check webform user views.
    $this->drupalGet('/webform/test_submission_views/drafts');
    $assert_session->responseNotContains('webform-results-table');
    $assert_session->responseContains('view-id-webform_submissions view-display-id-embed_default');

    // Clear global webform views replace.
    \Drupal::configFactory()
      ->getEditable('webform.settings')
      ->set('settings.default_submission_views_replace.webform_routes', [])
      ->save();

    // Check webform submissions entity list is displayed.
    $this->drupalGet('/admin/structure/webform/manage/test_submission_views/results/submissions');
    $assert_session->responseContains('webform-results-table');
    $assert_session->responseNotContains('view-id-webform_submissions view-display-id-embed_administer');

    // Check webform submissions views is moved to dedicated path.
    $this->clickLink('Administer submissions');
    $assert_session->addressEquals('/admin/structure/webform/manage/test_submission_views/results/submissions/admin');
    $assert_session->responseNotContains('webform-results-table');
    $assert_session->responseContains('view-id-webform_submissions view-display-id-embed_administer');

    // Check webform submissions entity list is displayed.
    $this->drupalGet('/webform/test_submission_views/drafts');
    $assert_session->responseContains('webform-results-table');
    $assert_session->responseNotContains('view-id-webform_submissions view-display-id-embed_default');

    // Check webform submissions entity list is moved to dedicated path.
    $this->clickLink('User submissions');
    $assert_session->addressEquals('/webform/test_submission_views/drafts/user');
    $assert_session->responseNotContains('webform-results-table');
    $assert_session->responseContains('view-id-webform_submissions view-display-id-embed_default');
  }

}
