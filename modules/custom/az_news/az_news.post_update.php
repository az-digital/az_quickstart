<?php

/**
 * Set new thumbnail image fields for existing news nodes.
 */
function az_news_post_update_after_1020701(&$sandbox) {
  if (!isset($sandbox['total'])) {
    $sandbox['current'] = 0;
    $sandbox['skipped'] = 0;
    $sandbox['total'] = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'az_news')
      ->count()
      ->execute();

    if (empty($sandbox['total'])) {
      \Drupal::messenger()
        ->addMessage('No news nodes found to process.');
      $sandbox['#finished'] = 1;
      return;
    }
  }

  $node_storage = \Drupal::entityTypeManager()->getStorage('node');
  $nodes_per_batch = 10;
  $nids = $node_storage->getQuery()
    ->accessCheck(FALSE)
    ->condition('type', 'az_news')
    ->range($sandbox['current'], $nodes_per_batch)
    ->execute();

  if (empty($nids)) {
    $sandbox['#finished'] = 1;
    return;
  }

  $news_nodes = $node_storage->loadMultiple($nids);
  foreach ($news_nodes as $node) {
    $node->set('field_az_featured_image_as_thumb', 1);
    $node->save();
    $sandbox['current']++;
  }
  \Drupal::messenger()
    ->addMessage($sandbox['current'] . ' news nodes processed to enable Use Featured Image as Thumb checkbox.');
  \Drupal::messenger()
    ->addMessage($sandbox['skipped'] . ' news nodes skipped.');

  if ($sandbox['current'] >= $sandbox['total']) {
    $sandbox['#finished'] = 1;
  }
  else {
    $sandbox['#finished'] = ($sandbox['current'] / $sandbox['total']);
  }
}
