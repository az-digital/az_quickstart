<?php

namespace Drupal\access_unpublished\Access;

use Drupal\content_moderation\Access\LatestRevisionCheck as ContentModerationLatestRevisionCheck;
use Drupal\Core\Access\AccessException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Access check for the entity moderation tab which supports access_unpublished.
 */
class LatestRevisionCheck extends ContentModerationLatestRevisionCheck {

  /**
   * The decorated access check.
   *
   * @var \Drupal\Core\Routing\Access\AccessInterface
   */
  protected $accessCheck;

  /**
   * LatestRevisionCheck constructor.
   *
   * @param \Drupal\Core\Routing\Access\AccessInterface $access_check
   *   Latest revision access check to decorate.
   */
  public function __construct(AccessInterface $access_check) {
    $this->accessCheck = $access_check;
  }

  /**
   * Checks that there is a pending revision available.
   *
   * This checker assumes the presence of an '_entity_access' requirement key
   * in the same form as used by EntityAccessCheck.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @see \Drupal\Core\Entity\EntityAccessCheck
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    /** @var \Drupal\Core\Access\AccessResultInterface $access */
    $access = $this->accessCheck->access($route, $route_match, $account);
    $entity = $this->loadEntity($route, $route_match);
    return $access->orIf(access_unpublished_entity_access($entity, 'view', $account));
  }

  /**
   * Returns the default revision of the entity this route is for.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   returns the Entity in question.
   *
   * @throws \Drupal\Core\Access\AccessException
   *   An AccessException is thrown if the entity couldn't be loaded.
   */
  protected function loadEntity(Route $route, RouteMatchInterface $route_match) {
    $entity_type = $route->getOption('_content_moderation_entity_type');

    if ($entity = $route_match->getParameter($entity_type)) {
      if ($entity instanceof EntityInterface) {
        return $entity;
      }
    }
    throw new AccessException(sprintf('%s is not a valid entity route. The LatestRevisionCheck access checker may only be used with a route that has a single entity parameter.', $route_match->getRouteName()));
  }

}
