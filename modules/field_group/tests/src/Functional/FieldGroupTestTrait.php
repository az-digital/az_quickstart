<?php

namespace Drupal\Tests\field_group\Functional;

/**
 * Provides common functionality for the FieldGroup test classes.
 */
trait FieldGroupTestTrait {

  /**
   * Create a new group.
   *
   * @param string $entity_type
   *   The entity type as string.
   * @param string $bundle
   *   The bundle of the entity type.
   * @param string $context
   *   The context for the group.
   * @param string $mode
   *   The view/form mode.
   * @param array $data
   *   Data for the field group.
   *
   * @return object
   *   An object that represents the field group.
   */
  protected function createGroup($entity_type, $bundle, $context, $mode, array $data) {

    if (!isset($data['format_settings'])) {
      $data['format_settings'] = [];
    }

    $data['format_settings'] += \Drupal::service('plugin.manager.field_group.formatters')->getDefaultSettings($data['format_type'], $context);

    $group_name_without_prefix = isset($data['group_name']) && is_string($data['group_name'])
      ? preg_replace('/^group_/', '', $data['group_name'])
      : mb_strtolower($this->randomMachineName());

    $group_name = 'group_' . $group_name_without_prefix;

    $field_group = (object) [
      'group_name' => $group_name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'mode' => $mode,
      'context' => $context,
      'children' => $data['children'] ?? [],
      'parent_name' => $data['parent'] ?? '',
      'weight' => $data['weight'] ?? 0,
      'label' => $data['label'] ?? $this->randomString(8),
      'format_type' => $data['format_type'],
      'format_settings' => $data['format_settings'],
      'region' => 'content',
    ];

    field_group_group_save($field_group);

    return $field_group;
  }

}
