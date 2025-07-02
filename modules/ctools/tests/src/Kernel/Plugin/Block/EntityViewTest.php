<?php

namespace Drupal\Tests\ctools\Kernel\Plugin\Block;

use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\ctools\Plugin\Block\EntityView;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the entity_view block plugin.
 *
 * @coversDefaultClass \Drupal\ctools\Plugin\Block\EntityView
 *
 * @group ctools
 */
class EntityViewTest extends KernelTestBase {

  use NodeCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'ctools',
    'filter',
    'node',
    'system',
    'user',
  ];

  /**
   * A page variant.
   *
   * @var \Drupal\page_manager\PageVariantInterface
   */
  protected $pageVariant;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['filter']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
  }

  /**
   * Tests plugin access.
   *
   * @covers ::access
   */
  public function testAccess() {
    // Create an unpublished node.
    $node = $this->createNode(['status' => 0]);

    $configuration = [
      'view_mode' => 'default',
    ];
    $definition = [
      'context_definitions' => [
        'entity' => new EntityContextDefinition('entity:node', NULL, TRUE, FALSE, NULL, $node),
      ],
      'provider' => 'ctools',
    ];
    $block = EntityView::create($this->container, $configuration, 'entity_view:node', $definition);
    $block->setContextValue('entity', $node);

    $access = $block->access(\Drupal::currentUser());
    $this->assertFalse($access);

    // Add a user than can see the unpublished block.
    $account = $this->createUser([], NULL, TRUE);
    $access = $block->access($account);
    $this->assertTrue($access);
  }

}
