<?php

namespace Drupal\Tests\auto_entitylabel\Functional;

use Drupal\auto_entitylabel\AutoEntityLabelManager;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests 'Preserve already created titles' option.
 *
 * @group auto_entitylabel
 *
 * @requires module token
 */
class PreserveTitlesOptionTest extends BrowserTestBase {

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
   * User variable.
   *
   * @var bool|\Drupal\user\Entity\User|false
   */
  protected $user;

  /**
   * Node storage variable.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'filter',
    'token',
    'auto_entitylabel',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->user = $this->drupalCreateUser([], '', TRUE);
    $this->drupalLogin($this->user);

    $this->nodeType = $this->createContentType(['type' => 'page']);

    $this->configFactory = $this->container->get('config.factory');
    $this->nodeStorage = $this->container->get('entity_type.manager')->getStorage('node');
  }

  /**
   * Tests disabled 'Preserve already created titles'.
   */
  public function testPreserveTitlesOptionDisabled() {
    $this->assertPreserveTitlesOption(FALSE, 10);
  }

  /**
   * Tests enabled 'Preserve already created titles'.
   */
  public function testPreserveTitlesOptionEnabled() {
    $this->assertPreserveTitlesOption(TRUE, 10);
  }

  /**
   * Asserts that 'Preserve already created titles' option works correctly.
   *
   * @param bool $value
   *   Value for 'preserve_titles' to be set.
   * @param int $numberOfNodes
   *   Number of testing nodes to be created.
   */
  public function assertPreserveTitlesOption(bool $value, int $numberOfNodes) {
    $editNodeId = rand(1, $numberOfNodes);
    $webAssert = $this->assertSession();
    $this->createTestNodes($numberOfNodes, 'page');
    $this->configFactory
      ->getEditable("auto_entitylabel.settings.node.{$this->nodeType->id()}")
      ->set('status', AutoEntityLabelManager::ENABLED)
      ->set('pattern', 'Test node [current-user:account-name]')
      ->set('preserve_titles', $value)
      ->save();
    $this->drupalGet('/node/' . $editNodeId . '/edit');
    $webAssert->buttonExists('Save')->click();
    if ($value) {
      $page = $this->nodeStorage->load($editNodeId);
      $this->assertNotEquals('Test node ' . $this->user->getAccountName(), $page->get('title')->value);
    }
    else {
      $page = $this->nodeStorage->load($editNodeId);
      $this->assertEquals('Test node ' . $this->user->getAccountName(), $page->get('title')->value);
    }
  }

  /**
   * Creates number of dummy nodes of provided type.
   *
   * @param int $numberOfNodes
   *   Number of nodes to be created.
   * @param string $nodeType
   *   Type of node to be created.
   */
  public function createTestNodes(int $numberOfNodes, string $nodeType) {
    for ($i = 0; $i < $numberOfNodes; $i++) {
      $this->drupalCreateNode([
        'type' => $nodeType,
        'title' => 'Testing node ' . $nodeType . ' ' . $i,
      ]);
    }
  }

}
