<?php

namespace Drupal\flag\Permissions;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\flag\FlagServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions for defined flags.
 */
class FlagPermissions implements ContainerInjectionInterface {

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * Constructs a FlagPermissions instance.
   *
   * @param \Drupal\flag\FlagServiceInterface $flag
   *   The flag service.
   */
  public function __construct(FlagServiceInterface $flag) {
    $this->flagService = $flag;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('flag'));
  }

  /**
   * Returns an array of dynamic flag permissions.
   *
   * @return array
   *   An array of permissions.
   *
   * @see Drupal\flag\FlagInterface::getPermissions()
   */
  public function permissions() {
    $permissions = [];

    // Get a list of flags from the FlagService.
    $flags = $this->flagService->getAllFlags();

    // Provide flag and unflag permissions for each flag.
    foreach ($flags as $flag) {
      $permissions += $flag->actionPermissions();
    }

    return $permissions;
  }

}
