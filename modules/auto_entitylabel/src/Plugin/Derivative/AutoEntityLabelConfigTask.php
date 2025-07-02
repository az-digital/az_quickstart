<?php

namespace Drupal\auto_entitylabel\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for Config Task.
 */
class AutoEntityLabelConfigTask extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates an FieldUiLocalTask object.
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
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type instanceof ContentEntityType) {
        $base_route = $entity_type->get("field_ui_base_route");
      }
      // Special handling of Taxonomy. See https://www.drupal.org/node/2822546
      elseif ($entity_type_id == "taxonomy_vocabulary") {
        $base_route = "entity.{$entity_type_id}.overview_form";
      }
      else {
        $base_route = "entity.{$entity_type_id}.edit_form";
      }
      if ($entity_type->hasLinkTemplate('auto-label')) {
        $this->derivatives["$entity_type_id.auto_label_tab"] = [
          'route_name' => "entity.{$entity_type_id}.auto_label",
          'title' => $this->t('Automatic label'),
          'base_route' => $base_route,
          'weight' => 100,
        ];
      }
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }
    return $this->derivatives;
  }

}
