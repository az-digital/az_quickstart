<?php

namespace Drupal\blazy\Skin;

/**
 * Provides skin manager service.
 */
class SkinManager extends SkinManagerBase implements SkinManagerInterface {

  /**
   * {@inheritdoc}
   */
  protected function getDependencies(): array {
    return ['blazy/dblazy'];
  }

}
