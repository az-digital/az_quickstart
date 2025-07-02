<?php

namespace Drupal\Tests\inline_entity_form\Kernel\Migrate;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\FieldConfigInterface;

/**
 * Tests migration of inline_entity_form field instances.
 *
 * @group inline_entity_form
 */
class MigrateFieldInstanceTest extends MigrateTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'inline_entity_form',
    'node',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig('node');
    $this->executeMigrations([
      'd7_node_type',
      'd7_field',
      'd7_field_instance',
    ]);
  }

  /**
   * Asserts various aspects of a field config entity.
   *
   * @param string $id
   *   The entity ID in the form ENTITY_TYPE.BUNDLE.FIELD_NAME.
   * @param string $expected_label
   *   The expected field label.
   * @param string $expected_field_type
   *   The expected field type.
   * @param bool $is_required
   *   Whether or not the field is required.
   * @param bool $expected_translatable
   *   Whether or not the field is expected to be translatable.
   */
  protected function assertEntity($id, $expected_label, $expected_field_type, $is_required, $expected_translatable) {
    [$expected_entity_type, $expected_bundle, $expected_name] = explode('.', $id);

    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = FieldConfig::load($id);
    $this->assertInstanceOf(FieldConfigInterface::class, $field);
    $this->assertEquals($expected_label, $field->label());
    $this->assertEquals($expected_field_type, $field->getType());
    $this->assertEquals($expected_entity_type, $field->getTargetEntityTypeId());
    $this->assertEquals($expected_bundle, $field->getTargetBundle());
    $this->assertEquals($expected_name, $field->getName());
    $this->assertEquals($is_required, $field->isRequired());
    $this->assertEquals($expected_entity_type . '.' . $expected_name, $field->getFieldStorageDefinition()->id());
    $this->assertEquals($expected_translatable, $field->isTranslatable());
  }

  /**
   * Asserts the settings of an entity reference field config entity.
   *
   * @param string $id
   *   The entity ID in the form ENTITY_TYPE.BUNDLE.FIELD_NAME.
   * @param string[] $target_bundles
   *   An array of expected target bundles.
   * @param string[] $sort
   *   An array of expected sort parameters.
   */
  protected function assertEntityReferenceFields($id, array $target_bundles, array $sort) {
    $field = FieldConfig::load($id);
    $handler_settings = $field->getSetting('handler_settings');
    $this->assertArrayHasKey('target_bundles', $handler_settings);
    foreach ($handler_settings['target_bundles'] as $target_bundle) {
      $this->assertContains($target_bundle, $target_bundles);
    }
    $this->assertArrayHasKey('sort', $handler_settings);
    $this->assertSame($sort, $handler_settings['sort']);
  }

  /**
   * Tests migrating D7 field instances to field_config entities.
   */
  public function testFieldInstances() {
    $this->assertEntity('node.test.body', 'Body', 'text_with_summary', FALSE, FALSE);
    $this->assertEntity('node.test.field_single', 'single', 'entity_reference', FALSE, FALSE);
    $this->assertEntity('node.test.field_multiple', 'multiple', 'entity_reference', FALSE, FALSE);

    $this->assertEntityReferenceFields(
      'node.test.field_single',
      ['page'], ['field' => 'language', 'direction' => 'DESC']
    );
    $this->assertEntityReferenceFields(
      'node.test.field_multiple',
      ['page', 'test'], ['field' => '_none', 'direction' => 'ASC']
    );
  }

}
