<?php

namespace Drupal\Tests\auto_entitylabel\Kernel;

use Drupal\auto_entitylabel\AutoEntityLabelManager;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests AutoEntityLabelManager methods.
 *
 * @group auto_entitylabel
 *
 * @requires module token
 */
class AutoEntityLabelManagerTest extends EntityKernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * Node type.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $nodeType;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * AutoEntityLabelManager service.
   *
   * @var mixed
   */
  protected $autoEntityLabelManager;

  /**
   * Node entity.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $entity;

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
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');

    $this->installSchema('user', 'users_data');
    $this->installSchema('node', ['node_access']);

    $this->installConfig(self::$modules);

    $this->nodeType = $this->createContentType(['type' => 'page']);
    $this->configFactory = $this->container->get('config.factory');
    $this->setConfiguration([
      'status' => AutoEntityLabelManager::DISABLED,
    ]);
    $this->entity = $this->createNode([
      'title' => 'Test Node',
      'uid' => 1,
      'type' => $this->nodeType->id(),
    ]);
    $this->createAutoEntityLabelManager();
  }

  /**
   * Tests hasLabel() method.
   */
  public function testHasLabel() {
    $this->assertTrue($this->autoEntityLabelManager->hasLabel());
  }

  /**
   * Tests setLabel() method.
   */
  public function testSetLabel() {
    $this->setConfiguration([
      'status' => AutoEntityLabelManager::ENABLED,
      'pattern' => 'Test Node',
    ]);
    $this->assertEquals('Test Node', $this->autoEntityLabelManager->setLabel());
  }

  /**
   * Tests hasAutoLabel() method.
   */
  public function testHasAutoLabel() {
    $this->setConfiguration([
      'status' => AutoEntityLabelManager::ENABLED,
    ]);
    $this->assertTrue($this->autoEntityLabelManager->hasAutoLabel());

    $this->setConfiguration([
      'status' => AutoEntityLabelManager::DISABLED,
    ]);
    $this->assertNotTrue($this->autoEntityLabelManager->hasAutoLabel());
  }

  /**
   * Tests hasOptionalAutoLabel() method.
   */
  public function testHasOptionalAutoLabel() {
    $this->setConfiguration([
      'status' => AutoEntityLabelManager::OPTIONAL,
    ]);
    $this->assertTrue($this->autoEntityLabelManager->hasOptionalAutoLabel());

    $this->setConfiguration([
      'status' => AutoEntityLabelManager::DISABLED,
    ]);
    $this->assertNotTrue($this->autoEntityLabelManager->hasOptionalAutoLabel());
  }

  /**
   * Tests autoLabelNeeded() method.
   */
  public function testAutoLabelNeeded() {
    $this->createAutoEntityLabelManager();
    $this->setConfiguration([
      'status' => AutoEntityLabelManager::ENABLED,
    ]);
    $this->assertTrue($this->autoEntityLabelManager->autoLabelNeeded());

    $this->autoEntityLabelManager->setLabel();
    $this->assertNotTrue($this->autoEntityLabelManager->autoLabelNeeded());
  }

  /**
   * Tests isTitlePreserved() method.
   */
  public function testIsTitlePreserved() {
    $this->setConfiguration([
      'preserve_titles' => FALSE,
    ]);
    $this->assertNotTrue($this->autoEntityLabelManager->isTitlePreserved());

    $this->setConfiguration([
      'preserve_titles' => TRUE,
    ]);
    $this->assertTrue($this->autoEntityLabelManager->isTitlePreserved());
  }

  /**
   * Tests getStatus() method.
   */
  public function testGetStatus() {
    $this->setConfiguration([
      'status' => AutoEntityLabelManager::DISABLED,
    ]);
    $this->assertEquals(AutoEntityLabelManager::DISABLED, $this->autoEntityLabelManager->getStatus());

    $this->setConfiguration([
      'status' => AutoEntityLabelManager::ENABLED,
    ]);
    $this->assertEquals(AutoEntityLabelManager::ENABLED, $this->autoEntityLabelManager->getStatus());

    $this->setConfiguration([
      'status' => AutoEntityLabelManager::OPTIONAL,
    ]);
    $this->assertEquals(AutoEntityLabelManager::OPTIONAL, $this->autoEntityLabelManager->getStatus());

    $this->setConfiguration([
      'status' => AutoEntityLabelManager::PREFILLED,
    ]);
    $this->assertEquals(AutoEntityLabelManager::PREFILLED, $this->autoEntityLabelManager->getStatus());
  }

  /**
   * Tests getPattern() method.
   */
  public function testGetPattern() {
    $this->setConfiguration([
      'pattern' => '',
    ]);
    $this->assertEquals('', $this->autoEntityLabelManager->getPattern());

    $this->setConfiguration([
      'pattern' => 'Testing pattern',
    ]);
    $this->assertEquals('Testing pattern', $this->autoEntityLabelManager->getPattern());

    $this->setConfiguration([
      'pattern' => '[node:author:name]',
    ]);
    $this->assertEquals('[node:author:name]', $this->autoEntityLabelManager->getPattern());
  }

  /**
   * Tests getLabelName() method.
   */
  public function testGetLabelName() {
    $this->assertEquals('title', $this->autoEntityLabelManager->getLabelName());
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

  /**
   * Creates new instance of AutoEntityLabelManager class.
   */
  public function createAutoEntityLabelManager() {
    $this->autoEntityLabelManager = new AutoEntityLabelManager(
      $this->entity,
      $this->configFactory,
      $this->container->get('entity_type.manager'),
      $this->container->get('token'),
      $this->container->get('module_handler'),
    );
  }

}
