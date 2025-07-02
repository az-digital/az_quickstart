<?php

namespace Drupal\views_bulk_operations\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\views\Views;
use Drupal\views_bulk_operations\Form\ViewsBulkOperationsFormTrait;

/**
 * Defines module access rules.
 */
class ViewsBulkOperationsAccess implements AccessInterface {

  use ViewsBulkOperationsFormTrait;

  /**
   * Object constructor.
   */
  public function __construct(
    protected readonly PrivateTempStoreFactory $tempStoreFactory
  ) {}

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\Core\Routing\RouteMatch $routeMatch
   *   The matched route.
   */
  public function access(AccountInterface $account, RouteMatch $routeMatch): AccessResult {
    $parameters = $routeMatch->getParameters()->all();

    if ($view = Views::getView($parameters['view_id'])) {
      // Set view arguments, sometimes needed for access checks.
      $view_data = $this->getTempstore($parameters['view_id'], $parameters['display_id'])->get($account->id());
      if ($view_data !== NULL) {
        $view->setArguments($view_data['arguments']);
      }
      if ($view->access($parameters['display_id'], $account)) {
        return AccessResult::allowed();
      }
    }
    return AccessResult::forbidden();
  }

}
