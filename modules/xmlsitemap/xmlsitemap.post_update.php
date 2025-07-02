<?php

/**
 * @file
 * Post update functions for XML Sitemap.
 */

/**
 * Force cache clear for new hook_entity_type_build().
 */
function xmlsitemap_post_update_entity_type_build_hook() {
  // Empty post-update hook.
}

/**
 * Force reindexing of all nodes that have unpublished future revisions.
 */
function xmlsitemap_post_update_reindex_future_revision_content() {
  if (\Drupal::moduleHandler()->moduleExists('content_moderation')) {
    /** @var \Drupal\workflows\WorkflowInterface[] $workflows */
    $workflows = \Drupal::entityTypeManager()->getStorage('workflow')->loadByPRoperties(['type' => 'content_moderation']);
    foreach ($workflows as $workflow) {
      /** @var \Drupal\content_moderation\Plugin\WorkflowType\ContentModeration $plugin */
      $plugin = $workflow->getTypePlugin();
      $entity_type_ids = $plugin->getEntityTypes();
      foreach ($entity_type_ids as $entity_type_id) {
        if ($bundles = $plugin->getBundlesForEntityType($entity_type_id)) {
          /** @var \Drupal\xmlsitemap\XmlSitemapLinkStorageInterface $linkStorage */
          $linkStorage = \Drupal::service('xmlsitemap.link_storage');
          $subquery = $linkStorage->getEntityLinkQuery($entity_type_id, $bundles);
          $subquery->condition('access', 0);
          $subquery->condition('status', 1);
          $query = $linkStorage->getEntityQuery($entity_type_id, $bundles, $subquery);
          $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
          $query->currentRevision();
          if ($entity_type->hasKey('published')) {
            $query->condition($entity_type->getKey('published'), TRUE);
          }
          $query->addTag(__FUNCTION__);
          if ($entity_ids = $query->execute()) {
            $limit = \Drupal::config('xmlsitemap.settings')->get('batch_limit');
            $chunks = array_chunk($entity_ids, $limit);
            foreach ($chunks as $entity_ids_chunk) {
              xmlsitemap_xmlsitemap_process_entity_links($entity_type_id, $entity_ids_chunk);
            }
          }
        }
      }
    }
  }
}
