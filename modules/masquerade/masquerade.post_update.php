<?php

/**
 * @file
 * Post update functions for Masquerade module.
 */

use Drupal\block\BlockInterface;
use Drupal\Core\Config\Entity\ConfigEntityUpdater;

/**
 * Add settings to to masquerade blocks for link to unmasquerade.
 */
function masquerade_post_update_add_block_setting_link(&$sandbox) {
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'block', function (BlockInterface $block) {
    if ($block->getPluginId() === 'masquerade') {
      $configuration = $block->getPlugin()->getConfiguration();
      if (!isset($configuration['show_unmasquerade_link'])) {
        $block->getPlugin()->setConfigurationValue('show_unmasquerade_link', FALSE);
        return TRUE;
      }
    }
    return FALSE;
  });
}

/**
 * Add configuration to force update last user's access time.
 */
function masquerade_post_update_add_settings() {
  \Drupal::configFactory()
    ->getEditable('masquerade.settings')
    ->set('update_user_last_access', TRUE)
    ->save(TRUE);
}
