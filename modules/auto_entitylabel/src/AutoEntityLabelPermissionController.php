<?php

namespace Drupal\auto_entitylabel;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions of the auto_entitylabel module.
 */
class AutoEntityLabelPermissionController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new AutoEntityLabelPermissionController instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * Returns an array of auto_entitylabel permissions.
   *
   * @return array
   *   Array with permissions.
   */
  public function autoEntityLabelPermissions() {
    $permissions = [];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      // Create a permission for each entity type to manage the entity
      // labels.
      if ($entity_type->hasLinkTemplate('auto-label') && $entity_type->hasKey('label')) {
        $permissions['administer ' . $entity_type_id . ' labels'] = [
          'title' => $this->t(
            '%entity_label: Administer automatic entity labels',
            ['%entity_label' => $entity_type->getLabel()]
          ),
          'restrict access' => TRUE,
        ];
      }
    }
    return $permissions;
  }

}
