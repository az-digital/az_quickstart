<?php

namespace Drupal\Tests\slick\Traits;

/**
 * A Trait common for Slick Unit tests.
 */
trait SlickUnitTestTrait {

  /**
   * Defines scoped definition.
   */
  protected function getSlickFormatterDefinition() {
    return [
      'namespace' => 'slick',
    ] + $this->getFormatterDefinition() + $this->getDefaulEntityFormatterDefinition();
  }

}
