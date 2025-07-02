<?php

namespace Drupal\Tests\auto_entitylabel\Kernel;

use Drupal\auto_entitylabel\AutoEntityLabelManager;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests for auto entity label.
 *
 * @group auto_entitylabel
 *
 * @requires module token
 */
class AutoEntityLabelTest extends EntityKernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * Node type.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $nodeType;

  /**
   * Config factory service variable.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'filter',
    'token',
    'auto_entitylabel',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installSchema('user', 'users_data');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(self::$modules);

    $this->nodeType = $this->createContentType(['type' => 'page']);
    $this->configFactory = $this->container->get('config.factory');
  }

  /**
   * Tests node creation with disabled settings.
   */
  public function testDisabledOption() {
    $this->setConfiguration([
      'status' => AutoEntityLabelManager::DISABLED,
    ]);
    $user = $this->createUser();
    $title = 'Test Node';
    $node = $this->createNode([
      'title' => $title,
      'uid' => $user->id(),
      'type' => $this->nodeType->id(),
    ]);
    $this->assertEquals($title, $node->getTitle(), 'The title is untouched.');
  }

  /**
   * Tests node creation with enabled settings.
   */
  public function testEnabledOption() {
    $this->setConfiguration([
      'status' => AutoEntityLabelManager::ENABLED,
      'pattern' => '[node:author:name]',
    ]);
    $user = $this->createUser();
    $node = $this->createNode([
      'uid' => $user->id(),
      'type' => $this->nodeType->id(),
    ]);
    $this->assertEquals($user->getAccountName(), $node->getTitle(), 'The title is set.');
  }

  /**
   * Test node creation with enabled settings, node id token and AFTER_SAVE set.
   *
   * Expect that the node id is filled.
   */
  public function testNodeId() {
    $this->setConfiguration([
      'status' => AutoEntityLabelManager::ENABLED,
      'pattern' => '[node:nid]',
      'new_content_behavior' => AutoEntityLabelManager::BEFORE_SAVE,
    ]);
    $user = $this->createUser();
    $node = $this->createNode([
      'uid' => $user->id(),
      'type' => $this->nodeType->id(),
    ]);

    // Node ids will change every time the test is run, so assertEquals
    // can't be used. Check that the token was replaced with something else.
    $this->assertNotEquals('[node:nid]', $node->getTitle(), 'The token was replaced.');
  }

  /**
   * Test that the post-insert hook doesn't affect node deletes.
   *
   * Note: According to the hook_post_action module the post-insert hook does
   * run during node deletes.
   */
  public function testNodeDelete() {

    $this->setConfiguration([
      'status' => AutoEntityLabelManager::ENABLED,
      'pattern' => '[node:nid]',
      'new_content_behavior' => AutoEntityLabelManager::AFTER_SAVE,
    ]);
    $user = $this->createUser();
    $node = $this->createNode([
      'uid' => $user->id(),
      'type' => $this->nodeType->id(),
    ]);

    // Delete the node.
    // This should not produce any errors.
    $node->delete();
  }

  /**
   * Tests node creation with optional settings.
   */
  public function testOptionalOption() {
    $this->setConfiguration([
      'status' => AutoEntityLabelManager::OPTIONAL,
      'pattern' => '[node:author:name]',
    ]);
    $user = $this->createUser();
    $title = 'Test Node';
    $node = $this->createNode([
      'title' => $title,
      'uid' => $user->id(),
      'type' => $this->nodeType->id(),
    ]);
    $this->assertEquals($title, $node->getTitle(), 'The title is untouched.');

    $node = $this->createNode([
      'title' => '',
      'uid' => $user->id(),
      'type' => $this->nodeType->id(),
    ]);
    $this->assertEquals($user->getAccountName(), $node->getTitle(), 'The title is set.');
  }

  /**
   * Tests node creation with 'Remove special characters' option selected.
   */
  public function testEscapeOption() {
    $this->setConfiguration([
      'status' => AutoEntityLabelManager::ENABLED,
      'pattern' => '[node:author:name] -?;: testing  123 ><*+',
      'escape' => TRUE,
    ]);
    $user = $this->createUser();
    $node = $this->createNode([
      'uid' => $user->id(),
      'type' => $this->nodeType->id(),
    ]);
    $this->assertEquals($user->getAccountName() . ' testing 123', $node->getTitle(), 'The title is set.');
  }

  /**
   * Tests node creation with add label before first save option.
   */
  public function testBeforeSaveOption() {
    $this->setConfiguration([
      'status' => AutoEntityLabelManager::ENABLED,
      'pattern' => '[node:author:name]',
      'new_content_behavior' => AutoEntityLabelManager::BEFORE_SAVE,
    ]);
    $user = $this->createUser();
    $node = $this->createNode([
      'uid' => $user->id(),
      'type' => $this->nodeType->id(),
    ]);
    $this->assertEquals($user->getAccountName(), $node->getTitle(), 'The title is set.');
  }

  /**
   * Sets the configuration values.
   *
   * @param array $params
   *   Array of values to be configured.
   */
  public function setConfiguration(array $params) {
    $autoEntityLabelSettings = $this->configFactory
      ->getEditable("auto_entitylabel.settings.node.{$this->nodeType->id()}");
    foreach ($params as $key => $value) {
      $autoEntityLabelSettings
        ->set($key, $value);
    }
    $autoEntityLabelSettings->save();
  }

}
