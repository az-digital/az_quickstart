<?php

namespace Drupal\az_secrets\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Service to check if secrets are configured and have values.
 */
class SecretsChecker {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a SecretsChecker object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Check if a key is configured with a value.
   *
   * @param string $key_id
   *   The key entity ID.
   *
   * @return bool
   *   TRUE if the key exists and has a value, FALSE otherwise.
   */
  public function hasKey(string $key_id): bool {
    try {
      $key_storage = $this->entityTypeManager->getStorage('key');
      $key = $key_storage->load($key_id);

      if ($key && method_exists($key, 'getKeyValue')) {
        $value = $key->getKeyValue();
        return !empty($value);
      }
    }
    catch (\Exception $e) {
      // Key storage not available.
    }

    return FALSE;
  }

  /**
   * Check if multiple keys are all configured with values.
   *
   * @param array $key_ids
   *   Array of key entity IDs.
   *
   * @return bool
   *   TRUE if all keys exist and have values, FALSE otherwise.
   */
  public function hasKeys(array $key_ids): bool {
    foreach ($key_ids as $key_id) {
      if (!$this->hasKey($key_id)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Get the value of a key if it exists.
   *
   * @param string $key_id
   *   The key entity ID.
   *
   * @return string|null
   *   The key value or NULL if not found.
   */
  public function getKeyValue(string $key_id): ?string {
    try {
      $key_storage = $this->entityTypeManager->getStorage('key');
      $key = $key_storage->load($key_id);

      if ($key && method_exists($key, 'getKeyValue')) {
        return $key->getKeyValue();
      }
    }
    catch (\Exception $e) {
      // Key storage not available.
    }

    return NULL;
  }

}
