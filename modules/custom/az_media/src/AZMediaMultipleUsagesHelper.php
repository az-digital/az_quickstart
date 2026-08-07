<?php

namespace Drupal\az_media;

use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Helper class for warning about media usage.
 */
class AZMediaMultipleUsagesHelper implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['warnMultipleUsages'];
  }

  /**
   * Prepares a managed_file by changing the remove button.
   *
   * @param array $element
   *   A structured array containing a managed_file.
   *
   * @return array
   *   The passed-in element with remove button changed.
   */
  public static function warnMultipleUsages(array $element) {

    $count = 0;
    // See which files this element applies to.
    if (!empty($element['#files'])) {
      $ids = [];
      foreach ($element['#files'] as $file) {
        // Get usage for file.
        $file_usage = \Drupal::service('file.usage')->listUsage($file);

        // Get media IDs that use the file.
        if (!empty($file_usage['file']['media'])) {
          $ids = array_merge($ids, array_keys($file_usage['file']['media']));
        }

      }
      // Query all reference fields which use this file.
      if (!empty($ids)) {
        $entityTypeManager = \Drupal::service('entity_type.manager');
        // Get all fields that reference media entities.
        $media_reference_fields = $entityTypeManager->getStorage('field_storage_config')
          ->loadByProperties([
            'type' => 'entity_reference',
            'settings' => ['target_type' => 'media'],
          ]);

        // Iterate through each field and check for references.
        foreach ($media_reference_fields as $field_name => $field_storage_config) {
          $entity_type_id = $field_storage_config->getTargetEntityTypeId();
          $storage = $entityTypeManager->getStorage($entity_type_id);

          // Get only the name of the field without the entity type.
          $keys = explode('.', $field_name);
          $identifier = $keys[1] ?? NULL;
          if (!$identifier) {
            continue;
          }
          // Query for entities that reference the media ID.
          $query = $storage->getQuery()
            ->condition($identifier, $ids, 'IN')
            ->accessCheck(FALSE);

          $count += $query->count()->execute();
        }
      }
    }
    if (!empty($element['remove_button']) && ($count > 1)) {
      $element['remove_button']['#value'] = t('Remove in @places places', [
        '@places' => $count,
      ]);
    }
    return $element;
  }

}
