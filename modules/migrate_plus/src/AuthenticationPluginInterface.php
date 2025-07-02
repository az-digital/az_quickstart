<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus;

/**
 * Defines an interface for authenticaion handlers.
 *
 * @see \Drupal\migrate_plus\Annotation\Authentication
 * @see \Drupal\migrate_plus\AuthenticationPluginBase
 * @see \Drupal\migrate_plus\AuthenticationPluginManager
 * @see plugin_api
 */
interface AuthenticationPluginInterface {

  /**
   * Performs authentication, returning any options to be added to the request.
   *
   * @return array
   *   Options (such as Authentication headers) to be added to the request.
   *
   * @link http://docs.guzzlephp.org/en/latest/request-options.html
   */
  public function getAuthenticationOptions(): array;

}
