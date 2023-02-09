<?php

namespace Drupal\az_publication\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Quickstart Citation Style entities.
 */
interface AZQuickstartCitationStyleInterface extends ConfigEntityInterface {

  /**
   * Returns the csl style.
   *
   * @return string
   *   The CSL style of this citation style.
   */
  public function getStyle();

  /**
   * Sets the csl style.
   *
   * @param string $style
   *   The desired CSL style.
   *
   * @return $this
   */
  public function setStyle($style);

}
