<?php

namespace Drupal\ctools\Testing;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Trait used for common entity creation methods.
 */
trait EntityCreationTrait {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a custom content type based on default settings.
   *
   * @param string $entity_type
   *   The type of entity to create.
   * @param array $values
   *   An array of settings to change from the defaults.
   *   Example: 'type' => 'foo'.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Created entity.
   */
  protected function createEntity($entity_type, array $values = []) {
    $storage = $this->getEntityTypeManager()->getStorage($entity_type);
    $entity = $storage->create($values);
    $status = $entity->save();
    \Drupal::service('router.builder')->rebuild();

    if ($this instanceof \PHPUnit\Framework\TestCase) {
      // phpcs:ignore
      $this->assertSame(SAVED_NEW, $status, (new FormattableMarkup('Created entity %id of type %type.', ['%id' => $entity->id(), '%type' => $entity_type]))->__toString()); //psp
    }
    else {
      // phpcs:ignore
      $this->assertEquals(SAVED_NEW, $status, (new FormattableMarkup('Created entity %id of type %type.', ['%id' => $entity->id(), '%type' => $entity_type]))->__toString());
    }

    return $entity;
  }

  /**
   * Retrieves the Entity Type Manager for the Entity.
   *
   * @return \Drupal\Core\Entity\EntityTypeManager|\Drupal\Core\Entity\EntityTypeManagerInterface|object|null
   * @throws \Exception
   */
  protected function getEntityTypeManager() {
    if (!isset($this->entityTypeManager)) {
      $this->entityTypeManager = $this->container->get('entity_type.manager');
    }
    return $this->entityTypeManager;
  }

}
