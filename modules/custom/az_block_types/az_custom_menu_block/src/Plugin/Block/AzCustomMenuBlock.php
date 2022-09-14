<?php

namespace Drupal\az_custom_menu_block\Plugin\Block;

use Drupal\menu_block\Plugin\Block\MenuBlock;

/**
 * Provides an extended Menu block.
 *
 * @Block(
 *   id = "az_menu_block",
 *   admin_label = @Translation("AZ Menu block"),
 *   category = @Translation("AZ Menus"),
 *   deriver = "Drupal\menu_block\Plugin\Derivative\MenuBlock",
 *   forms = {
 *     "settings_tray" = "\Drupal\system\Form\SystemMenuOffCanvasForm",
 *   },
 * )
 */
class AzCustomMenuBlock extends MenuBlock {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaults = parent::defaultConfiguration();
    $defaults['suggestion'] = strtr('vertical_pills', '-', '_');
    return $defaults;
  }

}
