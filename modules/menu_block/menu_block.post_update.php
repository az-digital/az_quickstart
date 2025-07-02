<?php

/**
 * @file
 * Post update functions for Menu block.
 */

use Drupal\block\BlockInterface;
use Drupal\Core\Config\Entity\ConfigEntityUpdater;

/**
 * Implement config schema for the menu_block settings follow.
 */
function menu_block_post_update_implement_schema_for_follow_and_follow_parent(&$sandbox = NULL) {
  if (!\Drupal::moduleHandler()->moduleExists('block')) {
    return;
  }

  \Drupal::classResolver(ConfigEntityUpdater::class)
    ->update($sandbox, 'block', function (BlockInterface $block) {
      if (strpos($block->getPluginId(), 'menu_block:') === 0) {
        $block_settings = $block->get('settings');
        $block_settings['follow'] = (bool) $block_settings['follow'];
        $block->set('settings', $block_settings);
        return TRUE;
      }
      return FALSE;
    });
}

/**
 * Add 'expand_all_items' values to menu_block.
 */
function menu_block_post_update_add_expand_all_items_key(&$sandbox = NULL) {
  // Check if the block module is enabled first.
  if (!\Drupal::moduleHandler()->moduleExists('block')) {
    return;
  }
  // Set the default value of expand_all_items for existing menu_block configs.
  \Drupal::classResolver(ConfigEntityUpdater::class)
    ->update($sandbox, 'block', function (BlockInterface $block) {
      if (strpos($block->getPluginId(), 'menu_block:') === 0) {
        $block_settings = $block->get('settings');
        // Sets expand_all_items to value of expand.
        $block_settings['expand_all_items'] = (bool) $block_settings['expand'];
        // Removes expand.
        unset($block_settings['expand']);
        $block->set('settings', $block_settings);
        return TRUE;
      }
      return FALSE;
    });
}
