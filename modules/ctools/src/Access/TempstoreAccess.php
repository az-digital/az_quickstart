<?php

namespace Drupal\ctools\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface as CoreAccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ctools\Access\AccessInterface as CToolsAccessInterface;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Symfony\Component\Routing\Route;

/**
 * Tempstore Access for ctools.
 */
class TempstoreAccess implements CoreAccessInterface {

  /**
   * The shared tempstore factory.
   *
   * @var \Drupal\Core\TempStore\SharedTempStoreFactory
   */
  protected $tempstore;

  /**
   * Constructor for access to shared tempstore.
   */
  public function __construct(SharedTempStoreFactory $tempstore) {
    $this->tempstore = $tempstore;
  }

  /**
   * Retreive the tempstore factory.
   */
  protected function getTempstore() {
    return $this->tempstore;
  }

  /**
   * Access method to find if user has access to a particular tempstore.
   *
   * @param \Symfony\Component\Routing\Route $route
   * @param \Drupal\Core\Routing\RouteMatchInterface $match
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   */
  public function access(Route $route, RouteMatchInterface $match, AccountInterface $account) {
    $tempstore_id = $match->getParameter('tempstore_id') ? $match->getParameter('tempstore_id') : $route->getDefault('tempstore_id');
    $id = $match->getParameter($route->getRequirement('_ctools_access'));
    if ($tempstore_id && $id) {
      $cached_values = $this->getTempstore()->get($tempstore_id)->get($id);
      if (!empty($cached_values['access']) && ($cached_values['access'] instanceof CToolsAccessInterface)) {
        $access = $cached_values['access']->access($account);
      }
      else {
        $access = AccessResult::allowed();
      }
    }
    else {
      $access = AccessResult::forbidden();
    }
    // The different wizards will have different tempstore ids and adding this
    // cache context allows us to nuance the access per wizard.
    $access->addCacheContexts(['url.query_args:tempstore_id']);
    return $access;
  }

}
