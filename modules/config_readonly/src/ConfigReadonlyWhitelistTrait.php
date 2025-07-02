<?php

namespace Drupal\config_readonly;

use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Trait for Config Readonly Whitelists.
 *
 * @package config_readonly
 */
trait ConfigReadonlyWhitelistTrait {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * An array to store the whitelist ignore patterns.
   *
   * @var string[]
   */
  protected $patterns = [];

  /**
   * Set the module handler.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke hooks.
   */
  protected function setModuleHandler(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * Get whitelist patterns.
   *
   * @return string[]
   *   The whitelist patterns.
   */
  protected function getWhitelistPatterns() {
    if (!$this->patterns) {
      $this->patterns = $this->moduleHandler->invokeAll('config_readonly_whitelist_patterns');
    }
    return $this->patterns;
  }

  /**
   * Check if the given name matches any whitelist pattern.
   *
   * @param string $name
   *   The config name.
   *
   * @return bool
   *   Whether or not there is a match.
   */
  protected function matchesWhitelistPattern($name) {
    // Check for matches.
    $patterns = $this->getWhitelistPatterns();
    if ($patterns) {
      foreach ($patterns as $pattern) {
        $escaped = str_replace('\*', '.*', preg_quote($pattern, '/'));
        if (preg_match('/^' . $escaped . '$/', $name)) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

}
