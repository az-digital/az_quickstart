<?php

namespace Drupal\externalauth;

use Drupal\user\UserInterface;

/**
 * Interface for the ExternalAuth service.
 */
interface ExternalAuthInterface {

  /**
   * Load a Drupal user based on an external authname.
   *
   * D7 equivalent: user_external_load().
   *
   * @param string $authname
   *   The unique, external authentication name provided by authentication
   *   provider.
   * @param string $provider
   *   The module providing external authentication.
   *
   * @return \Drupal\user\UserInterface|bool
   *   The loaded Drupal user.
   */
  public function load(string $authname, string $provider);

  /**
   * Log a Drupal user in based on an external authname.
   *
   * @param string $authname
   *   The unique, external authentication name provided by authentication
   *   provider.
   * @param string $provider
   *   The module providing external authentication.
   *
   * @return \Drupal\user\UserInterface|bool
   *   The logged in Drupal user.
   */
  public function login(string $authname, string $provider);

  /**
   * Register a Drupal user based on an external authname.
   *
   * The Drupal username of the account to be created defaults to the external
   * authentication name prefixed with the provider ID. The caller may enforce
   * a custom Drupal username by setting that value in $account_data['name'].
   *
   * @param string $authname
   *   The unique, external authentication name provided by authentication
   *   provider.
   * @param string $provider
   *   The module providing external authentication.
   * @param array $account_data
   *   An array of additional properties to be saved with the user entity. If
   *   the array contains a 'name' string value, it will be used as local Drupal
   *   username, otherwise a default local Drupal username will be computed as
   *   "{$provider}_{$authname}".
   * @param mixed $authmap_data
   *   Additional data to be stored in the authmap entry.
   *
   * @return \Drupal\user\UserInterface
   *   The registered Drupal user.
   */
  public function register(string $authname, string $provider, array $account_data = [], $authmap_data = NULL);

  /**
   * Login and optionally register a Drupal user based on an external authname.
   *
   * @param string $authname
   *   The unique, external authentication name provided by authentication
   *   provider.
   * @param string $provider
   *   The module providing external authentication.
   * @param array $account_data
   *   An array of additional properties to be saved with the user entity.
   * @param mixed $authmap_data
   *   Additional data to be stored in the authmap entry.
   *
   * @return \Drupal\user\UserInterface
   *   The logged in, and optionally registered, Drupal user.
   */
  public function loginRegister(string $authname, string $provider, array $account_data = [], $authmap_data = NULL);

  /**
   * Finalize logging in the external user.
   *
   * Encapsulates user_login_finalize.
   *
   * @param \Drupal\user\UserInterface $account
   *   The Drupal user to finalize login for.
   * @param string $authname
   *   The unique, external authentication name provided by authentication
   *   provider.
   * @param string $provider
   *   The module providing external authentication.
   *
   * @return \Drupal\user\UserInterface
   *   The logged in Drupal user.
   *
   * @codeCoverageIgnore
   */
  public function userLoginFinalize(UserInterface $account, string $authname, string $provider): UserInterface;

  /**
   * Link a pre-existing Drupal user to a given authname.
   *
   * @param string $authname
   *   The unique, external authentication name provided by authentication
   *   provider.
   * @param string $provider
   *   The module providing external authentication.
   * @param \Drupal\user\UserInterface $account
   *   The existing Drupal account to link.
   * @param mixed $authmap_data
   *   Additional data to be stored in the authmap entry.
   *
   * @return bool
   *   Whether the account was linked to.
   *   Returns FALSE if the account was already linked, TRUE otherwise.
   */
  public function linkExistingAccount(string $authname, string $provider, UserInterface $account, $authmap_data = NULL);

}
