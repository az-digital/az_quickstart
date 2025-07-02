<?php

namespace Drupal\Tests\webform\Functional\Block;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform block context.
 *
 * @group webform
 */
class WebformBlockContextTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'webform', 'webform_node', 'webform_test_block_context'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Manually install blocks.
    $contexts = [
      'webform' => '@webform.webform_route_context:webform',
      'webform_submission' => '@webform.webform_submission_route_context:webform_submission',
      'node' => '@node.node_route_context:node',
    ];
    foreach ($contexts as $type => $context) {
      $block = $this->drupalPlaceBlock('webform_test_block_context_block', ['label' => '{' . $type . ' context}']);
      $block->setVisibilityConfig('webform', [
        'id' => 'webform',
        'webforms' => ['contact' => 'contact'],
        'negate' => FALSE,
        'context_mapping' => [
          $type => $context,
        ],
      ]);
      $block->save();
    }
    $block = $this->drupalPlaceBlock('webform_test_block_context_block', ['label' => '{all contexts}']);
    $block->setVisibilityConfig('webform', [
      'id' => 'webform',
      'webforms' => ['contact' => 'contact'],
      'negate' => FALSE,
      'context_mapping' => $contexts,
    ]);
    $block->save();
  }

  /**
   * Tests webform block context.
   */
  public function testBlockContext() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);
    $webform = Webform::load('contact');

    // Check webform context.
    $this->drupalGet('/webform/contact');
    $assert_session->responseContains('{all contexts}');
    $assert_session->responseContains('{webform context}');

    // Check webform submission context.
    $sid = $this->postSubmissionTest($webform);
    $this->drupalGet("/admin/structure/webform/manage/contact/submission/$sid");
    $assert_session->responseContains('{all contexts}');
    $assert_session->responseContains('{webform_submission context}');

    // Check webform node context.
    $node = $this->drupalCreateNode(['type' => 'webform']);
    $node->webform->target_id = 'contact';
    $node->webform->status = 1;
    $node->save();
    $this->drupalGet('/node/' . $node->id());
    $assert_session->responseContains('{all contexts}');
    $assert_session->responseContains('{node context}');
  }

}
