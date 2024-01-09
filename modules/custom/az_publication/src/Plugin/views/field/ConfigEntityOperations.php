<?php

namespace Drupal\az_publication\Plugin\views\field;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to display entity label optionally linked to entity page.
 *
 * @ViewsField("config_entity_operations")
 */
class ConfigEntityOperations extends FieldPluginBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ConfigEntityOperations object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    // Ensure that the necessary properties are available.
    if (!isset($values->type) || !isset($values->entity)) {
      return [];
    }

    $entity_type = $values->type;
    $entity = $values->entity;
    $links = [];

    if ($this->entityTypeManager->hasHandler($entity_type, 'list_builder')) {
      try {
        $links = $this->entityTypeManager
          ->getListBuilder($entity_type)
          ->getOperations($entity);
      }
      catch (\Exception $e) {
        // Handle exceptions, log errors, etc.
      }
    }

    return [
      '#type' => 'operations',
      '#links' => $links,
    ];
  }

}
