<?php

namespace Drupal\workbench_access\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a deriver for taxonomy hierarchy selection plugins.
 */
class TaxonomyHierarchySelectionDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * Label.
   *
   * @var string
   */
  protected $label = 'Restricted Taxonomy Term selection: @name';

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new SectionViewsPluginDeriver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
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
    foreach ($this->entityTypeManager->getStorage('access_scheme')->loadMultiple() as $id => $scheme) {
      $this->derivatives[$id] = [
        'scheme' => $id,
        'label' => $scheme->label(),
      ] + $base_plugin_definition;
    }
    return $this->derivatives;
  }

}
