<?php

namespace Drupal\Tests\webform_options_limit\Functional;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Webform options entity reference limit test.
 *
 * @group webform_options_limit
 */
class WebformOptionsLimitEntityReferenceTest extends WebformBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'webform',
    'node',
    'webform_options_limit',
    'webform_options_limit_test',
  ];

  /**
   * Test options limit.
   */
  public function testOptionsLimit() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_handler_options_limit_ent');

    // Must login because webform and entity references are cached for
    // anonymous users.
    $this->drupalLogin($this->rootUser);

    // Check the entity select is not available.
    $this->drupalGet('/webform/test_handler_options_limit_ent');
    $assert_session->responseContains('options_limits_entity_select is not available');

    // Create three page nodes.
    $this->createContentType(['type' => 'page']);
    $node_1 = $this->createNode();
    $node_2 = $this->createNode();
    $node_3 = $this->createNode();

    // Check the entity select options are now populated.
    $this->drupalGet('/webform/test_handler_options_limit_ent');
    $assert_session->responseNotContains('options_limits_entity_select is not available');
    $assert_session->responseContains('<option value="' . $node_1->id() . '">');
    $assert_session->responseContains('<option value="' . $node_2->id() . '">');
    $assert_session->responseContains('<option value="' . $node_3->id() . '">');

    // Select node 1 three times.
    $this->postSubmission($webform, ['options_limits_entity_select' => [$node_1->id()]]);
    $this->postSubmission($webform, ['options_limits_entity_select' => [$node_1->id()]]);
    $this->postSubmission($webform, ['options_limits_entity_select' => [$node_1->id()]]);

    // Check the node is now disabled.
    $this->drupalGet('/webform/test_handler_options_limit_ent');
    $assert_session->responseContains('data-webform-select-options-disabled="1"');
  }

}
