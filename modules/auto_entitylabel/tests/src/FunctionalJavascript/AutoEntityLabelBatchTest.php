<?php

namespace Drupal\Tests\auto_entitylabel\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests batch operations on re-save.
 *
 * @group auto_entitylabel
 *
 * @requires module token
 */
class AutoEntityLabelBatchTest extends WebDriverTestBase {

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
   * Tests that re-save batch works correctly.
   */
  public function testBatchProcess() {
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $webAssert */
    $webAssert = $this->assertSession();
    $this->createTestNodes(10, 'page');
    $pagesIDs = $this->nodeStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'page')->execute();
    foreach ($this->nodeStorage->loadMultiple($pagesIDs) as $index => $page) {
      $this->assertEquals('Testing node page ' . ($index - 1), $page->get('title')->value);
      $page->save();
    }
    $this->configFactory
      ->getEditable("auto_entitylabel.settings.node.{$this->nodeType->id()}")
      ->set('status', 1)
      ->set('pattern', 'Test node [current-user:account-name]')
      ->save();
    $this->drupalGet('/admin/structure/types/manage/page/auto-label');
    $webAssert->pageTextContains('AUTOMATIC LABEL GENERATION FOR');
    $webAssert->fieldExists('status');
    $webAssert->fieldExists('pattern');
    $webAssert->fieldExists('save');
    $webAssert->fieldExists('chunk');
    $edit = [
      'save' => TRUE,
      'chunk' => '5',
    ];
    $this->submitForm($edit, 'Save configuration');
    $webAssert->waitForText('The configuration options have been saved.');
    $webAssert->pageTextContains('Resaved 10 labels.');
    foreach ($this->nodeStorage->loadMultiple($pagesIDs) as $page) {
      $this->assertEquals('Test node ' . $this->user->getAccountName(), $page->get('title')->value);
    }
  }

  /**
   * Creates number of nodes of provided type.
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
