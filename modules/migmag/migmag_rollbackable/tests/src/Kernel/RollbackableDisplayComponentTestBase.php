<?php

namespace Drupal\Tests\migmag_rollbackable\Kernel;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;

/**
 * Base class for testing rollbackable entity display destinations.
 *
 * This a base class for testing the rollbackable entity display component
 * migration destination plugins.
 */
abstract class RollbackableDisplayComponentTestBase extends RollbackableDestinationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
  ];

  /**
   * Returns the form- or a view display configuration entity.
   *
   * @param string $type
   *   The 'type' of the display. This can be 'view', or 'form'.
   * @param string $entity_type
   *   The entity type ID to which the display belongs.
   * @param string $bundle
   *   The entity bundle to which the display belongs.
   * @param string $view_mode
   *   The view mode of the display.
   *
   * @return \Drupal\Core\Entity\Display\EntityFormDisplayInterface|\Drupal\Core\Entity\Display\EntityViewDisplayInterface
   *   The entity form- or view display entity. It might be either a new entity,
   *   or it also might be disabled.
   *
   * @throws \LogicException
   *   When the specified 'type' parameter doesn't equals to 'view' or 'form'.
   */
  protected function getDisplayEntity(string $type, string $entity_type, string $bundle, string $view_mode) {
    $display_repository = \Drupal::service('entity_display.repository');
    assert($display_repository instanceof EntityDisplayRepositoryInterface);

    $method = NULL;
    switch ($type) {
      case 'view':
        $method = 'getViewDisplay';
        break;

      case 'form':
        $method = 'getFormDisplay';
        break;

      default:
        throw new \LogicException(
          sprintf(
            "The first argument, '\$type' should be either 'form' or 'view', but '%s' was given",
            $type
          )
        );
    }

    return $display_repository->{$method}($entity_type, $bundle, $view_mode);
  }

}
