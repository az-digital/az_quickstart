<?php

declare(strict_types=1);

namespace Drupal\Tests\linkit\Kernel;

/**
 * Tests the entity matcher deriver.
 *
 * @group linkit
 */
class EntityMatcherDeriverTest extends LinkitKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'block_content', 'node', 'field'];

  /**
   * The matcher manager.
   *
   * @var \Drupal\linkit\MatcherManager
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['block_content']);
    $this->installEntitySchema('block_content');

    $this->installEntitySchema('node');
    $this->installConfig(['field', 'node']);

    $this->manager = $this->container->get('plugin.manager.linkit.matcher');
  }

  /**
   * Tests the deriver.
   */
  public function testDeriver() {
    $definition = $this->manager->getDefinition('entity:block_content', FALSE);
    $this->assertNull($definition);
    $definition = $this->manager->getDefinition('entity:node', FALSE);
    $this->assertNotNull($definition);
  }

}
