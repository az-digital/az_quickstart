<?php

namespace Drupal\masquerade\Session;

use Drupal\Core\Session\MetadataBag as CoreMetadataBag;

/**
 * Decorates service session_manager.metadata_bag to store required flag.
 *
 * @internal
 *   Implementation could change later.
 */
class MetadataBag extends CoreMetadataBag {

  /**
   * The key used to store the masquerading user ID in the session.
   */
  const MASQUERADE = 'masquerading';

  /**
   * Sets the masquerading identifier.
   *
   * @param string $uid
   *   The per-session identifier of masquerading user.
   */
  public function setMasquerade($uid) {
    $this->meta[static::MASQUERADE] = $uid;
  }

  /**
   * Get the masquerading identifier.
   *
   * @return string|null
   *   The per-session masquerade identifier or null when no value is set.
   */
  public function getMasquerade() {
    if (isset($this->meta[static::MASQUERADE])) {
      return $this->meta[static::MASQUERADE];
    }
  }

  /**
   * Clear the masquerade identifier.
   */
  public function clearMasquerade() {
    unset($this->meta[static::MASQUERADE]);
  }

}
