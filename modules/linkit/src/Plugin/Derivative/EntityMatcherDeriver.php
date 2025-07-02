<?php

namespace Drupal\linkit\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides matchers based on active entities.
 *
 * @see plugin_api
 */
class EntityMatcherDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates an EntityMatcherDeriver object.
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
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      $canonical = $entity_type->getLinkTemplate('canonical');
      $edit_form = $entity_type->getLinkTemplate('edit-form');

      // Only entities that has a distinct canonical URI that is not the same
      // as the edit-form URI will be derived.
      if (($entity_type instanceof ContentEntityTypeInterface && $canonical && $canonical !== $edit_form) || $entity_type_id === 'media') {
        $this->derivatives[$entity_type_id] = $base_plugin_definition;
        $this->derivatives[$entity_type_id]['id'] = $base_plugin_definition['id'] . ':' . $entity_type_id;
        $this->derivatives[$entity_type_id]['label'] = $entity_type->getLabel();
        $this->derivatives[$entity_type_id]['target_entity'] = $entity_type_id;
        $this->derivatives[$entity_type_id]['base_plugin_label'] = (string) $base_plugin_definition['label'];
      }
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
