<?php

namespace Drupal\paragraphs;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheCollector;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;

/**
 * A cache collector that caches IDs for the paragraphs_type entity icon UUIDs.
 */
class ParagraphsTypeIconUuidLookup extends CacheCollector {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a ParagraphsTypeIconUuidLookup instance.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(CacheBackendInterface $cache, LockBackendInterface $lock, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct('paragraphs_type_icon_uuid', $cache, $lock);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function resolveCacheMiss($key) {
    $ids = $this->entityTypeManager->getStorage('file')->getQuery()
      ->condition('uuid', $key)
      ->accessCheck(TRUE)
      ->execute();

    // Only cache if there is a match, otherwise creating new entities would
    // require to invalidate the cache.
    $id = reset($ids);
    if ($id) {
      $this->storage[$key] = $id;
      $this->persist($key);
    }
    return $id;
  }

}
