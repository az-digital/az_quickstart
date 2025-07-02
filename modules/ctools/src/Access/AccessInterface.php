<?php

namespace Drupal\ctools\Access;

use Drupal\Core\Session\AccountInterface;

/**
 * Ctools Access Interface.
 */
interface AccessInterface {

  /**
   * Provides the access method for accounts.
   */
  public function access(AccountInterface $account);

}
