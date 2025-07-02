<?php

namespace Drupal\Tests\devel\Kernel;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;

/**
 * Test Load with References.
 *
 * @group devel
 */
class DevelEntityToArrayTest extends EntityKernelTestBase {

  use EntityReferenceFieldCreationTrait;

  /**
   * The entity type used in this test.
   *
   * @var string
   */
  protected $entityType = 'entity_test';

  /**
   * The entity type that is being referenced.
   *
   * @var string
   */
  protected $referencedEntityType = 'entity_test_rev';

  /**
   * The bundle used in this test.
   *
   * @var string
   */
  protected $bundle = 'entity_test';

  /**
   * The name of the field used in this test.
   *
   * @var string
   */
  protected $fieldName = 'field_test';

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['entity_test', 'devel'];

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test_rev');

    $user = $this->createUser(permissions: ['access devel information'], values: ['name' => 'test']);

    /** @var \Drupal\Core\Session\AccountProxyInterface $current_user */
    $current_user = $this->container->get('current_user');
    $current_user->setAccount($user);

    // Create a field.
    $this->createEntityReferenceField(
      $this->entityType,
      $this->bundle,
      $this->fieldName,
      'Field test',
      $this->referencedEntityType,
      'default',
      ['target_bundles' => [$this->bundle]],
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );
  }

  /**
   * Test method.
   */
  public function testWithReferences(): void {
    // Create the parent entity.
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($this->entityType)
      ->create(['type' => $this->bundle]);

    // Create three target entities and attach them to parent field.
    $target_entities = [];
    $reference_field = [];
    for ($i = 0; $i < 3; ++$i) {
      $target_entity = $this->container->get('entity_type.manager')
        ->getStorage($this->referencedEntityType)
        ->create([
          'type' => $this->bundle,
          'name' => 'Related ' . $i,
        ]);
      $target_entity->save();
      $target_entities[] = $target_entity;
      $reference_field[]['target_id'] = $target_entity->id();
    }

    // Set the field value.
    $entity->{$this->fieldName}->setValue($reference_field);
    $entity->save();

    /** @var \Drupal\devel\DevelDumperManagerInterface $dumper */
    $dumper = $this->container->get('devel.dumper');
    $result = $dumper->export($entity, NULL, 'drupal_variable', TRUE);
    for ($i = 0; $i < 3; ++$i) {
      $this->assertStringContainsString('Related ' . $i, (string) $result, 'The referenced entities are present in the dumper output.');
    }
  }

}
