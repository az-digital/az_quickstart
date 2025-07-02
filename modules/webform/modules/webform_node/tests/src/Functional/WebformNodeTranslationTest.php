<?php

namespace Drupal\Tests\webform_node\Functional;

/**
 * Tests for webform node translation.
 *
 * @group webform_node
 */
class WebformNodeTranslationTest extends WebformNodeBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_node', 'webform_node_test_translation'];

  /**
   * Tests webform node translation.
   */
  public function testNodeTranslation() {
    $assert_session = $this->assertSession();

    $node = $this->createWebformNode('webform_node_test_translation', ['title' => 'English node']);

    // Check computed token uses the English title.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->hiddenFieldValueEquals('computed_token', 'English node');

    // Create spanish node.
    $node->addTranslation('es', ['title' => 'Spanish node'])->save();

    // Check computed token uses the Spanish title.
    $this->drupalGet('/es/node/' . $node->id());
    $assert_session->hiddenFieldValueNotEquals('computed_token', 'English node');
    $assert_session->hiddenFieldValueEquals('computed_token', 'Spanish node');
  }

}
