<?php

namespace Drupal\Tests\inline_entity_form\Kernel\Migrate;

use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * Tests migration of inline_entity_form widgets settings.
 *
 * @group inline_entity_form
 */
class MigrateFieldInstanceWidgetSettingsTest extends MigrateTestBase {

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
    $this->installEntitySchema('node');
    $this->executeMigrations([
      'd7_node_type',
      'd7_field',
      'd7_field_instance',
      'd7_field_instance_widget_settings',
    ]);
  }

  /**
   * Asserts various aspects of a form display entity.
   *
   * @param string $id
   *   The entity ID.
   * @param string $expected_entity_type
   *   The expected entity type to which the display settings are attached.
   * @param string $expected_bundle
   *   The expected bundle to which the display settings are attached.
   */
  protected function assertEntity($id, $expected_entity_type, $expected_bundle) {
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $entity */
    $entity = EntityFormDisplay::load($id);
    $this->assertInstanceOf(EntityFormDisplayInterface::class, $entity);
    $this->assertSame($expected_entity_type, $entity->getTargetEntityTypeId());
    $this->assertSame($expected_bundle, $entity->getTargetBundle());
  }

  /**
   * Asserts various aspects of a particular component of a form display.
   *
   * @param string $display_id
   *   The form display ID.
   * @param string $component_id
   *   The component ID.
   * @param string $widget_type
   *   The expected widget type.
   * @param string $weight
   *   The expected weight of the component.
   */
  protected function assertComponent($display_id, $component_id, $widget_type, $weight) {
    $component = EntityFormDisplay::load($display_id)->getComponent($component_id);
    $this->assertIsArray($component);
    $this->assertSame($widget_type, $component['type']);
    $this->assertSame($weight, $component['weight']);
  }

  /**
   * Test that migrated view modes can be loaded using D8 APIs.
   */
  public function testWidgetSettings() {
    $this->assertEntity('node.test.default', 'node', 'test');
    $this->assertComponent('node.test.default', 'body', 'text_textarea_with_summary', -4);
    $this->assertComponent('node.test.default', 'field_single', 'inline_entity_form_simple', -3);
    $this->assertComponent('node.test.default', 'field_multiple', 'inline_entity_form_complex', -2);
  }

}
