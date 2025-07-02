<?php

namespace Drupal\flag\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derivative class for entity flag type plugin.
 */
class EntityFlagTypeDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EntityFlagTypeDeriver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_def) {
    $derivatives = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_id => $entity_type) {
      // Skip config entity types.
      if (!$entity_type instanceof ContentEntityTypeInterface) {
        continue;
      }
      $derivatives[$entity_id] = [
        'title' => $entity_type->getLabel(),
        'entity_type' => $entity_id,
        'config_dependencies' => [
          'module' => [
            $entity_type->getProvider(),
          ],
        ],
      ] + $base_plugin_def;
    }

    return $derivatives;
  }

}
