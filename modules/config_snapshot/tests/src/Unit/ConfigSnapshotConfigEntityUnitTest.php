<?php

namespace Drupal\Tests\config_snapshot\Unit;

use Drupal\Core\Config\StorageInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\config_snapshot\Entity\ConfigSnapshot;

/**
 * @coversDefaultClass \Drupal\config_snapshot\Entity\ConfigSnapshot
 * @group config_snapshot
 */
class ConfigSnapshotConfigEntityUnitTest extends UnitTestCase {

  /**
   * The configuration snapshot entity.
   *
   * @var \Drupal\config_snapshot\Entity\ConfigSnapshot
   */
  protected $entity;

  /**
   * The entity type used for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityType;

  /**
   * The entity type manager used for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The UUID generator used for testing.
   *
   * @var \Drupal\Component\Uuid\UuidInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $uuid;

  /**
   * The extension name used for testing.
   *
   * @var string
   */
  protected $extensionName;

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();
    $this->entityType = $this->createMock('\Drupal\Core\Entity\EntityTypeInterface');
    $this->entityType->expects($this->any())
      ->method('getProvider')
      ->willReturn('entity');

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    $this->uuid = $this->createMock('\Drupal\Component\Uuid\UuidInterface');

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('uuid', $this->uuid);
    \Drupal::setContainer($container);

    $snapshot_set = $this->randomMachineName(8);
    $extension_type = 'module';
    $this->extensionName = $this->randomMachineName(8);

    $values = [
      'snapshotSet' => $snapshot_set,
      'extensionType' => $extension_type,
      'extensionName' => $this->extensionName,
    ];
    $this->entity = new ConfigSnapshot($values, 'config_snapshot.snapshot');
  }

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependencies() {
    $this->entityTypeManager->expects($this->any())
      ->method('getDefinition')
      ->willReturn($this->entityType);

    $dependencies = $this->entity->calculateDependencies()->getDependencies();
    $this->assertArrayNotHasKey('config', $dependencies);
    $this->assertContains($this->extensionName, $dependencies['module']);
  }

  /**
   * @covers ::clearItem
   * @covers ::getItem
   * @covers ::getItems
   * @covers ::setItem
   */
  public function testItems() {
    $expected_item = [
      'collection' => StorageInterface::DEFAULT_COLLECTION,
      'name' => 'example',
      'data' => [
        'something' => 'some_value',
      ],
    ];

    // Set a new item.
    $this->entity->setItem($expected_item['collection'], $expected_item['name'], $expected_item['data']);

    $items = $this->entity->getItems();
    $this->assertEquals(1, count($items));
    $this->assertEquals($expected_item, $items[0]);
    $item = $this->entity->getItem($expected_item['collection'], $expected_item['name']);
    $this->assertEquals($expected_item, $item);

    // Reset an existing item.
    $expected_new_data = [
      'something_else' => 'some_new_value',
    ];
    $this->entity->setItem($expected_item['collection'], $expected_item['name'], $expected_new_data);
    $item = $this->entity->getItem($expected_item['collection'], $expected_item['name']);
    $this->assertEquals($expected_new_data, $item['data']);

    // Clear an item.
    $this->entity->clearItem($expected_item['collection'], $expected_item['name']);
    $item = $this->entity->getItem($expected_item['collection'], $expected_item['name']);
    $this->assertNull($item);
  }

}
