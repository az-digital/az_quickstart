<?php

namespace Drupal\Tests\inline_entity_form\Kernel\Migrate;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldStorageConfigInterface;

/**
 * Tests migration of inline_entity_form fields.
 *
 * @group inline_entity_form
 */
class MigrateFieldTest extends MigrateTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigration('d7_field');
  }

  /**
   * Asserts various aspects of a field_storage_config entity.
   *
   * @param string $id
   *   The entity ID in the form ENTITY_TYPE.FIELD_NAME.
   * @param string $expected_type
   *   The expected field type.
   * @param bool $expected_translatable
   *   Whether or not the field is expected to be translatable.
   * @param int $expected_cardinality
   *   The expected cardinality of the field.
   */
  protected function assertEntity($id, $expected_type, $expected_translatable, $expected_cardinality) {
    [$expected_entity_type, $expected_name] = explode('.', $id);

    /** @var \Drupal\field\FieldStorageConfigInterface $field */
    $field = FieldStorageConfig::load($id);
    $this->assertInstanceOf(FieldStorageConfigInterface::class, $field);
    $this->assertEquals($expected_name, $field->getName());
    $this->assertEquals($expected_type, $field->getType());
    $this->assertEquals($expected_translatable, $field->isTranslatable());
    $this->assertEquals($expected_entity_type, $field->getTargetEntityTypeId());

    if ($expected_cardinality === 1) {
      $this->assertFalse($field->isMultiple());
    }
    else {
      $this->assertTrue($field->isMultiple());
    }
    $this->assertEquals($expected_cardinality, $field->getCardinality());
  }

  /**
   * Tests migrating D7 fields to field_storage_config entities.
   */
  public function testFields() {
    $this->assertEntity('node.body', 'text_with_summary', TRUE, 1);
    $this->assertEntity('node.field_single', 'entity_reference', TRUE, 1);
    $this->assertEntity('node.field_multiple', 'entity_reference', TRUE, 1);

    $field = FieldStorageConfig::load('node.field_single');
    $this->assertEquals('node', $field->getSetting('target_type'));
    $field = FieldStorageConfig::load('node.field_multiple');
    $this->assertEquals('node', $field->getSetting('target_type'));
  }

}
