<?php

namespace Drupal\Tests\webform_submission_log\Functional;

use Drupal\Tests\webform_node\Functional\WebformNodeBrowserTestBase;
use Drupal\Tests\webform_submission_log\Traits\WebformSubmissionLogTrait;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform node submission log.
 *
 * @group webform_submission_log
 */
class WebformSubmissionLogNodeTest extends WebformNodeBrowserTestBase {

  use WebformSubmissionLogTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'webform', 'webform_node', 'webform_submission_log'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_submission_log'];

  /**
   * Tests webform submission log.
   */
  public function testSubmissionLog() {
    global $base_path;

    $assert_session = $this->assertSession();

    $node = $this->createWebformNode('test_submission_log');
    $nid = $node->id();

    $sid = $this->postNodeSubmission($node);
    $submission = WebformSubmission::load($sid);
    $log = $this->getLastSubmissionLog();
    $this->assertEquals($log->lid, 1);
    $this->assertEquals($log->sid, 1);
    $this->assertEquals($log->uid, 0);
    $this->assertEquals($log->handler_id, '');
    $this->assertEquals($log->operation, 'submission created');
    $this->assertEquals($log->message, '@title created.');
    $this->assertEquals($log->variables, ['@title' => $submission->label()]);
    $this->assertEquals($log->webform_id, 'test_submission_log');
    $this->assertEquals($log->entity_type, 'node');
    $this->assertEquals($log->entity_id, $node->id());

    // Login.
    $this->drupalLogin($this->rootUser);

    // Check webform node results log table has record.
    $this->drupalGet("node/$nid/webform/results/log");
    $assert_session->statusCodeEquals(200);
    $assert_session->responseNotContains('No log messages available.');
    $assert_session->responseContains('<a href="' . $base_path . 'node/' . $nid . '/webform/submission/' . $sid . '/log">' . $sid . '</a>');
    $assert_session->responseContains($this->t('@title created.', ['@title' => $submission->label()]));

    // Check webform node submission log tab.
    $this->drupalGet("node/$nid/webform/submission/$sid/log");
    $assert_session->statusCodeEquals(200);
  }

}
