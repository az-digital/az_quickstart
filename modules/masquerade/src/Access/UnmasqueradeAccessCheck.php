<?php

namespace Drupal\masquerade\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\masquerade\Masquerade;

/**
 * Checks access based on the masquerade status of the user.
 */
class UnmasqueradeAccessCheck implements AccessInterface {

  /**
   * The masquerade service.
   *
   * @var \Drupal\masquerade\Masquerade
   */
  protected $masquerade;

  /**
   * Constructs a new UnmasqueradeAccessCheck object.
   *
   * @param \Drupal\masquerade\Masquerade $masquerade
   *   The masquerade service.
   */
  public function __construct(Masquerade $masquerade) {
    $this->masquerade = $masquerade;
  }

  /**
   * Check to see if user is masquerading.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access() {
    return AccessResult::allowedIf($this->masquerade->isMasquerading())
      ->addCacheContexts(['session.is_masquerading']);
  }

}
