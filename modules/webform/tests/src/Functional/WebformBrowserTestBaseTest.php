<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\webform\Entity\Webform;

/**
 * Test the webform test base class.
 *
 * @group webform_browser
 */
class WebformBrowserTestBaseTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'block', 'user'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_ajax'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
  }

  /**
   * Test base  helper methods.
   */
  public function testWebformBase() {
    $assert_session = $this->assertSession();

    // Check that test webform is installed.
    $this->assertNotNull(Webform::load('test_ajax'));

    // Check create webform.
    $test_webform = $this->createWebform();
    $this->assertNotNull($test_webform);

    $webform = Webform::load('contact');

    // Check post submission return NULL if post fails.
    $sid = $this->postSubmission($webform);
    $this->assertFalse($sid);

    // Login root user.
    $this->drupalLogin($this->rootUser);

    // Check post test submission returns an sid.
    $sid = $this->postSubmissionTest($webform);
    $this->assertNotNull($sid);

    // Check submission load not from cache.
    $webform_submission = $this->loadSubmission($sid);
    $this->assertNotNull($webform_submission);
    $this->assertEquals('contact', $webform_submission->getWebform()->id());

    // Check submission email.
    $last_email = $this->getLastEmail();
    $this->assertEquals('webform_contact_email_notification', $last_email['id']);

    // Check purge submission deletes the submission.
    $this->purgeSubmissions();
    $webform_submission = $this->loadSubmission($sid);
    $this->assertNull($webform_submission);

    // Check place blocks.
    $this->placeBlocks();
    $this->drupalGet('/webform/contact');
    $assert_session->responseContains('<h1>Contact</h1>');
    $assert_session->responseContains('<h2 id="system-breadcrumb" class="visually-hidden">Breadcrumb</h2>');
    $assert_session->responseContains('<h2 class="visually-hidden">Primary tabs</h2>');
  }

}
