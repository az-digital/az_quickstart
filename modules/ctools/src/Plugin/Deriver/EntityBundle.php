<?php

namespace Drupal\ctools\Plugin\Deriver;

use Drupal\Core\Plugin\Context\EntityContextDefinition;

/**
 * Deriver that creates a condition for each entity type with bundles.
 *
 * @deprecated in ctools:8.x-1.10. Will be removed before ctools:4.1.0.
 *   Use \Drupal\Core\Entity\Plugin\Condition\Deriver\EntityBundle instead.
 */
class EntityBundle extends EntityDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

    // Do not define any derivatives on Drupal 9.3+, instead, replace the core
    // class in ctools_condition_info_alter().
    if (\version_compare(\Drupal::VERSION, '9.3', '>')) {
      return [];
    }

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->hasKey('bundle')) {
        $this->derivatives[$entity_type_id] = $base_plugin_definition;
        $this->derivatives[$entity_type_id]['label'] = $this->getEntityBundleLabel($entity_type);
        $this->derivatives[$entity_type_id]['context_definitions'] = [
          "$entity_type_id" => new EntityContextDefinition('entity:' . $entity_type_id),
        ];
      }
    }
    return $this->derivatives;
  }

  /**
   * Provides the bundle label with a fallback when not defined.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type we are looking the bundle label for.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The entity bundle label or a fallback label.
   */
  protected function getEntityBundleLabel($entity_type) {

    if ($label = $entity_type->getBundleLabel()) {
      return $this->t('@label', ['@label' => $label]);
    }

    $fallback = $entity_type->getLabel();
    if ($bundle_entity_type = $entity_type->getBundleEntityType()) {
      // This is a better fallback.
      $fallback = $this->entityTypeManager->getDefinition($bundle_entity_type)->getLabel();
    }

    return $this->t('@label bundle', ['@label' => $fallback]);

  }

}
