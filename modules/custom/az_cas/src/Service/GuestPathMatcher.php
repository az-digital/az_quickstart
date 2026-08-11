<?php

namespace Drupal\az_cas\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\path_alias\AliasManagerInterface;

/**
 * Service for checking if paths should be protected by guest authentication.
 */
class GuestPathMatcher {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The path alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $pathAliasManager;

  /**
   * Constructs a new GuestPathMatcher.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   * @param \Drupal\path_alias\AliasManagerInterface $path_alias_manager
   *   The path alias manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    PathMatcherInterface $path_matcher,
    AliasManagerInterface $path_alias_manager,
  ) {
    $this->configFactory = $config_factory;
    $this->pathMatcher = $path_matcher;
    $this->pathAliasManager = $path_alias_manager;
  }

  /**
   * Checks if a path should be protected by guest authentication.
   *
   * @param string $path
   *   The path to check.
   *
   * @return bool
   *   TRUE if the path should be protected, FALSE otherwise.
   */
  public function isPathProtected($path) {
    // Get guest mode settings.
    $az_cas_settings = $this->configFactory->get('az_cas.settings');
    $guest_mode = $az_cas_settings->get('guest_mode');
    $guest_auth_paths = $az_cas_settings->get('guest_auth_paths') ?: [];

    // If guest mode is disabled, no paths are protected.
    if (!$guest_mode) {
      return FALSE;
    }

    // Get alias if this is a system path, or system path if this is an alias.
    $path_alias = $this->pathAliasManager->getAliasByPath($path);
    $system_path = $this->pathAliasManager->getPathByAlias($path);

    // Paths to check - the current path and its alias/system path counterpart.
    $paths_to_check = [$path];
    if ($path_alias !== $path) {
      $paths_to_check[] = $path_alias;
    }
    if ($system_path !== $path && $system_path !== $path_alias) {
      $paths_to_check[] = $system_path;
    }

    // Check if any of these paths match the guest auth paths.
    foreach ($guest_auth_paths as $pattern) {
      foreach ($paths_to_check as $check_path) {
        if ($this->pathMatcher->matchPath($check_path, $pattern)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

}
