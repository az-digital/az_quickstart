<?php

namespace Drupal\az_layouts\Controller;

use Drupal\layout_builder\Controller\ChooseBlockController;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * Defines a controller to choose a new block.
 *
 * @internal
 *   Controller classes are internal.
 */
class AZChooseBlockController extends ChooseBlockController {

  /**
   * Provides the UI for choosing a new inline block.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage.
   * @param int $delta
   *   The delta of the section to splice.
   * @param string $region
   *   The region the block is going in.
   *
   * @return array
   *   A render array.
   */
  public function inlineBlockList(SectionStorageInterface $section_storage, $delta, $region) {
    $build = parent::inlineBlockList($section_storage, $delta, $region);

    // Hide the back button since we skipped the non-custom block selection.
    if (isset($build['back_button'])) {
      unset($build['back_button']);
    }

    return $build;
  }

}
