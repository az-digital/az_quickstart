<?php

namespace Drupal\Tests\metatag\Unit;

use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\metatag\Form\MetatagDefaultsForm;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This class validates all the entities types that are supported by metatag.
 *
 * @group metatag
 */
class MetatagSupportedEntitiesTest extends UnitTestCase {

  /**
   * Tests the getSupportedEntityTypes method from MetatagDefaultsForm.
   */
  public function testGetSupportedEntityTypes() {
    // Create a mock entity type manager.
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);

    // Create a mock entity type definition.
    $example_entity_type = $this->createMock(ContentEntityType::class);
    $example_entity_type
      ->method('get')
      ->withAnyParameters()
      ->willReturn(
        [
          'links' => [],
        ],
      );
    $example_entity_type
      ->method('getLabel')
      ->withAnyParameters()
      ->willReturn('Example entity type');

    $entity_type_manager->method('getDefinitions')
      ->withAnyParameters()
      ->willReturn([
        // The only supported entity type.
        'page' => $example_entity_type,
        // The list bellow is the same as the one in the original method.
        'block_content' => $example_entity_type,
        'contact_message' => $example_entity_type,
        'menu_link_content' => $example_entity_type,
        'path_alias' => $example_entity_type,
        'shortcut' => $example_entity_type,
        'commerce_order' => $example_entity_type,
        'commerce_payment' => $example_entity_type,
        'commerce_payment_method' => $example_entity_type,
        'commerce_promotion' => $example_entity_type,
        'commerce_promotion_coupon' => $example_entity_type,
        'commerce_shipment' => $example_entity_type,
        'commerce_shipping_method' => $example_entity_type,
        'commerce_stock_location' => $example_entity_type,
        'linkcheckerlink' => $example_entity_type,
        'redirect' => $example_entity_type,
        'salesforce_mapped_object' => $example_entity_type,
        'webform_submission' => $example_entity_type,
      ]);

    // Instantiate drupal container.
    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager);
    \Drupal::setContainer($container);

    // Call the method under test.
    $result = MetatagDefaultsForm::getSupportedEntityTypes();

    // Assert that the result contains the expected supported entity types.
    $this->assertArrayHasKey('page', $result, 'Only the page entity type should be supported.');

    // Only one supported entity type should be returned.
    $this->assertCount(1, $result, 'Only one entity type should be supported.');
  }

}
