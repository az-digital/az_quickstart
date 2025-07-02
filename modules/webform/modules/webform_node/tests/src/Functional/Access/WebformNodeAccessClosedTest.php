<?php

namespace Drupal\Tests\webform_node\Functional\Access;

use Drupal\Tests\webform_node\Functional\WebformNodeBrowserTestBase;

/**
 * Tests for webform node closed access.
 *
 * @group webform_node
 */
class WebformNodeAccessClosedTest extends WebformNodeBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_node'];

  /**
   * Tests webform node closed access.
   */
  public function testAccessClosed() {
    $assert_session = $this->assertSession();

    $node = $this->createWebformNode('contact');
    $nid = $node->id();

    $account = $this->drupalCreateUser(['access content']);

    /* ********************************************************************** */

    $this->drupalLogin($account);

    // Check webform node access allowed.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->statusCodeEquals(200);
    $assert_session->fieldValueEquals('name', $account->getAccountName());
    $assert_session->fieldValueEquals('email', $account->getEmail());

    // Check webform access allowed with source entity.
    $this->drupalGet('/webform/contact', ['query' => ['source_entity_type' => 'node', 'source_entity_id' => $nid]]);
    $assert_session->statusCodeEquals(200);
    $assert_session->fieldValueEquals('name', $account->getAccountName());
    $assert_session->fieldValueEquals('email', $account->getEmail());

    // Close the webform via the node.
    $node->webform->status = FALSE;
    $node->save();

    // Check webform node access denied.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->statusCodeEquals(200);
    $assert_session->fieldNotExists('name');
    $assert_session->fieldNotExists('email');
    $assert_session->responseContains('Sorry… This form is closed to new submissions.');

    // Check webform access denied with source entity.
    $this->drupalGet('/webform/contact', ['query' => ['source_entity_type' => 'node', 'source_entity_id' => $nid]]);
    $assert_session->statusCodeEquals(200);
    $assert_session->fieldNotExists('name');
    $assert_session->fieldNotExists('email');
    $assert_session->responseContains('Sorry… This form is closed to new submissions.');
  }

}
