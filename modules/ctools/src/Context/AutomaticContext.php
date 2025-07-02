<?php

namespace Drupal\ctools\Context;

use Drupal\Core\Plugin\Context\Context;

/**
 * Provides a class to indicate that this context is always present.
 *
 * @internal
 *
 * @todo Move into core.
 */
class AutomaticContext extends Context {

  /**
   * Returns TRUE if this context is automatic and always available.
   *
   * @return bool
   *   If the context is automatic or not.
   */
  public function isAutomatic() {
    return TRUE;
  }

}
