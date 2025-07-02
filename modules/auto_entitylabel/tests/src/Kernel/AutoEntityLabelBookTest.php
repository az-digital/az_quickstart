<?php

namespace Drupal\Tests\auto_entitylabel\Kernel;

use Drupal\auto_entitylabel\AutoEntityLabelManager;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests for auto entity label and core book module interactions.
 *
 * @group auto_entitylabel
 *
 * @requires module token
 */
class AutoEntityLabelBookTest extends EntityKernelTestBase {

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
    'book',
    'filter',
    'token',
    'auto_entitylabel',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installSchema('user', 'users_data');
    $this->installSchema('node', ['node_access']);
    $this->installSchema('book', ['book']);
    $this->installConfig(self::$modules);

    $this->nodeType = $this->createContentType(['type' => 'book_page']);
    $this->configFactory = $this->container->get('config.factory');

    $book_config = $this->config('book.settings');
    $allowed_types = $book_config->get('allowed_types');
    $allowed_types[] = $this->nodeType->id();
    $book_config->set('allowed_types', $allowed_types)->save();
  }

  /**
   * Tests book node creation with enabled settings.
   */
  public function testBookOption() {
    $this->setConfiguration([
      'status' => AutoEntityLabelManager::ENABLED,
      'pattern' => '[node:author:name]',
    ]);
    $user = $this->createUser();
    $node = $this->createNode([
      'uid' => $user->id(),
      'type' => $this->nodeType->id(),
      'book' => [
        'bid' => 0,
        'pid' => -1,
        'weight' => 0,
      ],
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
