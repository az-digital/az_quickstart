<?php

namespace Drupal\Tests\paragraphs\Traits;

/**
 * Provides helper methods for Drupal 8.3.x and 8.4.x versions.
 */
trait ParagraphsCoreVersionUiTestTrait {

  /**
   * Places commonly used blocks in a consistent order.
   */
  protected function placeDefaultBlocks() {
    // Place the system main block explicitly and first to have a consistent
    // block order before and after Drupal 9.4
    $this->drupalPlaceBlock('system_main_block', ['weight' => -1, 'region' => 'content']);
    // Place the breadcrumb, tested in fieldUIAddNewField().
    $this->drupalPlaceBlock('system_breadcrumb_block', ['region' => 'content']);
    $this->drupalPlaceBlock('local_tasks_block', ['region' => 'content']);
    $this->drupalPlaceBlock('local_actions_block', ['region' => 'content']);
    $this->drupalPlaceBlock('page_title_block', ['region' => 'content']);
  }

}
