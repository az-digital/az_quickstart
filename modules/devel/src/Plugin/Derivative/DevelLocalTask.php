<?php

namespace Drupal\devel\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local task definitions for all entity bundles.
 *
 * @see \Drupal\devel\Controller\EntityDebugController
 * @see \Drupal\devel\Routing\RouteSubscriber
 */
class DevelLocalTask extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  final public function __construct() {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id): static {
    $instance = new static();
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->stringTranslation = $container->get('string_translation');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      $has_edit_path = $entity_type->hasLinkTemplate('edit-form');
      $has_canonical_path = $entity_type->hasLinkTemplate('devel-render');

      if ($has_edit_path || $has_canonical_path) {
        $this->derivatives[$entity_type_id . '.devel_tab'] = [
          'route_name' => sprintf('entity.%s.', $entity_type_id) . ($has_edit_path ? 'devel_load' : 'devel_render'),
          'title' => $this->t('Devel'),
          'base_route' => sprintf('entity.%s.', $entity_type_id) . ($has_canonical_path ? "canonical" : "edit_form"),
          'weight' => 100,
        ];

        $this->derivatives[$entity_type_id . '.devel_definition_tab'] = [
          'route_name' => sprintf('entity.%s.devel_definition', $entity_type_id),
          'title' => $this->t('Definition'),
          'parent_id' => sprintf('devel.entities:%s.devel_tab', $entity_type_id),
          'weight' => 100,
        ];

        $this->derivatives[$entity_type_id . 'devel_path_alias_tab'] = [
          'route_name' => sprintf('entity.%s.devel_path_alias', $entity_type_id),
          'title' => $this->t('Path alias'),
          'parent_id' => sprintf('devel.entities:%s.devel_tab', $entity_type_id),
          'weight' => 100,
        ];

        if ($has_canonical_path) {
          $this->derivatives[$entity_type_id . '.devel_render_tab'] = [
            'route_name' => sprintf('entity.%s.devel_render', $entity_type_id),
            'weight' => 100,
            'title' => $this->t('Render'),
            'parent_id' => sprintf('devel.entities:%s.devel_tab', $entity_type_id),
          ];
        }

        if ($has_edit_path) {
          $this->derivatives[$entity_type_id . '.devel_load_tab'] = [
            'route_name' => sprintf('entity.%s.devel_load', $entity_type_id),
            'weight' => 100,
            'title' => $this->t('Load'),
            'parent_id' => sprintf('devel.entities:%s.devel_tab', $entity_type_id),
          ];
          $this->derivatives[$entity_type_id . '.devel_load_with_references_tab'] = [
            'route_name' => sprintf('entity.%s.devel_load_with_references', $entity_type_id),
            'weight' => 100,
            'title' => $this->t('Load (with references)'),
            'parent_id' => sprintf('devel.entities:%s.devel_tab', $entity_type_id),
          ];
        }
      }
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
