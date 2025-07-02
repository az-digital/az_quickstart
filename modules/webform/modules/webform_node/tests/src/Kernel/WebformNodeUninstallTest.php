<?php

namespace Drupal\Tests\webform_node\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests if webform nodes exist.
 *
 * Tests that the Webform node module cannot be uninstalled
 *  if webform nodes exist.
 *
 * @group webform_node
 */
class WebformNodeUninstallTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'field', 'text', 'user', 'node', 'webform', 'webform_node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('webform');
    $this->installEntitySchema('webform_submission');
    $this->installSchema('webform', ['webform']);
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['system', 'node', 'webform', 'webform_node']);
    // For uninstall to work.
    $this->installSchema('user', ['users_data']);
  }

  /**
   * Tests the webform_node_uninstall() method.
   */
  public function testWebformNodeUninstall() {
    \Drupal::moduleHandler()->loadInclude('webform_node', 'install');

    // Check that webform node module can not be installed.
    $this->assertNotEmpty(webform_node_requirements('install'), 'Webform node module can not be installed.');

    // No nodes exist.
    $validation_reasons = \Drupal::service('module_installer')->validateUninstall(['webform_node']);
    $this->assertEquals($validation_reasons, [], 'The webform_node module is not required.');

    $node = Node::create(['title' => $this->randomString(), 'type' => 'webform']);
    $node->save();

    // Check webform node module can't be uninstalled.
    $validation_reasons = \Drupal::service('module_installer')->validateUninstall(['webform_node']);
    $this->assertEquals($validation_reasons['webform_node'], ['To uninstall Webform node, delete all content that has the Webform content type.']);

    $node->delete();

    // Uninstall the Webform node module and check that all webform node have been deleted.
    \Drupal::service('module_installer')->uninstall(['webform_node']);
    $this->assertNull(NodeType::load('webform'), "The webform node type does not exist.");

    // Check that webform node module can be installed.
    $this->assertEmpty(webform_node_requirements('install'), 'Webform node module can be installed.');
  }

}
