<?php

namespace Drupal\Tests\block_field\Kernel;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Tests\block_field\Traits\BlockFieldTestTrait;

/**
 * Tests the formatters functionality.
 *
 * @group entity_reference
 */
class BlockFieldFormatterTest extends EntityKernelTestBase {

  use BlockFieldTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'block_field', 'block_field_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['user']);
    // Add a block field to the test entity.
    $this->installEntitySchema('entity_test');
    $this->createBlockField('entity_test', 'entity_test', 'field_test', 'Field test', 'default', [], FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
  }

  /**
   * Tests that block access cache metadata is propagated.
   */
  public function testBlockAccessCacheMetadata() {
    $renderer = $this->container->get('renderer');

    // Create a referencing entity.
    $referencing_entity = $this->container->get('entity_type.manager')
      ->getStorage('entity_test')
      ->create(['name' => $this->randomMachineName()]);

    $referencing_entity->field_test->plugin_id = 'block_field_test_access';
    $referencing_entity->field_test->settings = [
      'label' => 'Custom access.',
      'label_display' => TRUE,
      'access' => TRUE,
    ];
    $referencing_entity->save();
    $items = $referencing_entity->get('field_test');
    $formatter_manager = $this->container->get('plugin.manager.field.formatter');

    // Get all the existing formatters.
    foreach ($formatter_manager->getOptions('block_field') as $formatter => $name) {
      // Build the renderable array for the field.
      $build = $items->view(['type' => $formatter, 'settings' => []]);
      $markup = $renderer->renderRoot($build);

      // Assert cache tags are propagated correctly.
      $this->assertEquals($build['#cache']['tags'], ['custom_tag'], sprintf('The formatter %s does not propagate tags correctly.', $name));

      // Assert block contents are properly rendered.
      $this->assertStringContainsString('Custom access.', (string) $markup, sprintf('The contents of the block are missing when using the %s formatter', $name));
    }

    // Assert that the tags are still propagated with a denied access.
    $referencing_entity->field_test->settings = [
      'label' => 'Custom access.',
      'label_display' => TRUE,
      'access' => FALSE,
    ];
    $referencing_entity->save();

    // Get all the existing formatters.
    foreach ($formatter_manager->getOptions('block_field') as $formatter => $name) {
      // Build the renderable array for the field.
      $build = $items->view(['type' => $formatter, 'settings' => []]);

      $markup = $renderer->renderRoot($build);
      // Assert cache tags are propagated correctly.
      $this->assertEquals($build['#cache']['tags'], ['custom_tag'], sprintf('The formatter %s does not propagate tags correctly.', $name));

      // Assert block contents are not rendered.
      $this->assertStringNotContainsString('Custom access.', $markup, sprintf('The block contents show even when we denied access when using the %s formatter', $name));
    }
  }

}
