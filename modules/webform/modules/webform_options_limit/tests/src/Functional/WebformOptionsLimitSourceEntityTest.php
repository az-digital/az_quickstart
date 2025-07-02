<?php

namespace Drupal\Tests\webform_options_limit\Functional;

use Drupal\Tests\webform_node\Functional\WebformNodeBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Webform options limit source entity test.
 *
 * @group webform_options_limit
 */
class WebformOptionsLimitSourceEntityTest extends WebformNodeBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'webform',
    'webform_node',
    'webform_options_limit',
    'webform_options_limit_test',
  ];

  /**
   * Test options limit source entity.
   */
  public function testSourceEntity() {
    $assert_session = $this->assertSession();

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_handler_options_limit');
    $node = $this->createWebformNode('test_handler_options_limit');

    // Check that the webform node option A and webform option A are both open.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->responseContains('A [1 remaining]');
    $this->drupalGet('/webform/test_handler_options_limit');
    $assert_session->responseContains('A [1 remaining]');

    // Create a webform node submission.
    $this->postNodeSubmission($node);

    // Check that the webform node option A is closed and
    // webform option A is open.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->responseContains('A [0 remaining]');
    $this->drupalGet('/webform/test_handler_options_limit');
    $assert_session->responseContains('A [1 remaining]');

    // Create a webform submission.
    $this->postSubmission($webform);

    // Check that the webform node option A and webform option A
    // are both closed.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->responseContains('A [0 remaining]');

    $this->drupalGet('/webform/test_handler_options_limit');
    $assert_session->responseContains('A [0 remaining]');

    // Purge submission.
    $this->purgeSubmissions();

    // Disable source entity support for the handler.
    $webform->getHandler('options_limit_default')
      ->setSetting('limit_source_entity', FALSE);
    $webform->save();

    // Check that the webform node option A and webform option A are both open.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->responseContains('A [1 remaining]');
    $this->drupalGet('/webform/test_handler_options_limit');
    $assert_session->responseContains('A [1 remaining]');

    // Create one submission which set option limit for both the node and the
    // webform.
    $this->postNodeSubmission($node);

    // Check that the webform node option A and webform option A
    // are both closed.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->responseContains('A [0 remaining]');
    $this->drupalGet('/webform/test_handler_options_limit');
    $assert_session->responseContains('A [0 remaining]');
  }

}
