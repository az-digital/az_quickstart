<?php

namespace Drupal\metatag_routes\Helper;

use Drupal\Core\Routing\CurrentRouteMatch;

/**
 * Class MetatagRoutesHelper.
 *
 * @package Drupal\metatag_routes\Helper
 */
class MetatagRoutesHelper implements MetatagRoutesHelperInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The route match.
   */
  public function __construct(CurrentRouteMatch $current_route_match) {
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * @{@inheritdoc}
   */
  public function createMetatagRouteId($route_name, $params = NULL) {
    if ($params) {
      return $route_name . $this->getParamsHash(json_encode($params));
    }

    return $route_name;
  }

  /**
   * @{@inheritdoc}
   */
  public function getCurrentMetatagRouteId() {
    $route_name = $this->currentRouteMatch->getRouteName();
    $params = $this->currentRouteMatch->getRawParameters()->all();

    if ($params) {
      return $route_name . $this->getParamsHash(json_encode($params));
    }

    return $route_name;
  }

  /**
   * Return hash of given parameters.
   */
  protected function getParamsHash($params) {
    return md5(serialize($params));
  }

}
