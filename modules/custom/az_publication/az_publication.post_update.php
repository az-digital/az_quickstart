<?php

use Drupal\node\Entity\Node;

/**
 * Remap authors to have role assignments.
 */
function az_publication_post_update_remap_authors_to_have_role_assignments(&$sandbox) {
  // Initialize or retrieve the batch progress information.
  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    $sandbox['current_node'] = 0;

    // Query all node IDs of type 'az_publication'.
    $sandbox['nids'] = \Drupal::entityQuery('node')
      ->condition('type', 'az_publication')
      ->accessCheck(FALSE)
      ->execute();

    $sandbox['max'] = count($sandbox['nids']);
  }

  // Process nodes in batches.
  // Number of nodes to process per batch.
  $limit = 50;
  $nids_slice = array_slice($sandbox['nids'], $sandbox['progress'], $limit);
  $nodes = Node::loadMultiple($nids_slice);
  $time = \Drupal::service('datetime.time');

  foreach ($nodes as $node) {
    if ($node->hasField('field_az_authors') && !$node->get('field_az_authors')->isEmpty() &&
      $node->hasField('field_az_contributors') && $node->get('field_az_contributors')->isEmpty()) {
      $old_value = $node->get('field_az_authors')->getValue();
      $new_value = [];
      // Remapped contributors are (by definition) authors.
      // Previously this was the only assignment that existed.
      // Target_ids go unchanged.
      foreach ($old_value as $item) {
        $item['role'] = 'author';
        $new_value[] = $item;
      }

      // Update the new contributor entity role references.
      if (!empty($new_value)) {
        // @todo should authors be removed?
        $node->set('field_az_contributors', $new_value);
        $node->setNewRevision(TRUE);
        $node->isDefaultRevision(TRUE);
        // Construct a detailed message.
        $revision_log_message = "Publication authors remapped to contributors.";
        $node->setRevisionLogMessage($revision_log_message);
        $node->setRevisionCreationTime($time->getRequestTime());
        $node->setRevisionUserId(1);
        $node->save();
      }
    }

    $sandbox['progress']++;
    $sandbox['current_node'] = $node->id();
  }

  // Inform the batch API about the progress.
  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);
}
