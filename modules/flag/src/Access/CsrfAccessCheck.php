<?php

namespace Drupal\flag\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\CsrfAccessCheck as OriginalCsrfAccessCheck;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Proxy class to the core CSRF access checker allowing anonymous requests.
 *
 * As per https://www.drupal.org/node/2319205 this is OK and desired.
 */
class CsrfAccessCheck implements AccessInterface {

  /**
   * Original.
   *
   * @var \Drupal\Core\Access\CsrfAccessCheck
   */
  protected $original;

  /**
   * Account Interface.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * CsrfAccessCheck constructor.
   *
   * @param \Drupal\Core\Access\CsrfAccessCheck $original
   *   Original.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account.
   */
  public function __construct(OriginalCsrfAccessCheck $original, AccountInterface $account) {
    $this->original = $original;
    $this->account = $account;
  }

  /**
   * Checks access based on a CSRF token for the request for auth users.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result, always allowed for anonymous users.
   */
  public function access(Route $route, Request $request, RouteMatchInterface $route_match) {
    // As the original returns AccessResult::allowedif the token validates,
    // we do the same for anonymous.
    return $this->account->isAnonymous() ? AccessResult::allowed() : $this->original->access($route, $request, $route_match);
  }

}
