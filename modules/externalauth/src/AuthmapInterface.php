<?php

namespace Drupal\externalauth;

use Drupal\user\UserInterface;

/**
 * Interface for Authmap service.
 */
interface AuthmapInterface {

  /**
   * Save an external authname for a given Drupal user.
   *
   * @param \Drupal\user\UserInterface $account
   *   The Drupal user account.
   * @param string $provider
   *   The name of the service providing external authentication.
   * @param string $authname
   *   The unique, external authentication name provided by authentication
   *   provider.
   * @param mixed $data
   *   Optional extra (serialized) data to store with the authname.
   */
  public function save(UserInterface $account, string $provider, string $authname, $data = NULL);

  /**
   * Get the external authname for a given user ID.
   *
   * @param int $uid
   *   The Drupal user ID.
   * @param string $provider
   *   The name of the service providing external authentication.
   *
   * @return string|bool
   *   The external authname / ID, or FALSE.
   */
  public function get(int $uid, string $provider);

  /**
   * Get the external authname & extra data for a given user ID.
   *
   * @param int $uid
   *   The Drupal user ID.
   * @param string $provider
   *   The name of the service providing external authentication.
   *
   * @return array|bool
   *   An array with authname & data values.
   */
  public function getAuthData(int $uid, string $provider);

  /**
   * Get all external authnames for a given user ID.
   *
   * @param int $uid
   *   The Drupal user ID.
   *
   * @return array
   *   An array of external authnames / IDs for the given user ID, keyed by
   *   provider name.
   */
  public function getAll(int $uid): array;

  /**
   * Get a Drupal user ID based on an authname.
   *
   * The authname will be provided by an authentication provider.
   *
   * @param string $authname
   *   The external authname as provided by the authentication provider.
   * @param string $provider
   *   The name of the service providing external authentication.
   *
   * @return int|bool
   *   The Drupal user ID or FALSE.
   */
  public function getUid(string $authname, string $provider);

  /**
   * Delete authmap entries for a given Drupal user ID.
   *
   * Deletion will be restricted to the specified provider, if passed.
   *
   * @param int $uid
   *   The Drupal user ID.
   * @param string|null $provider
   *   (optional) The name of the service providing external authentication.
   */
  public function delete(int $uid, ?string $provider = NULL);

  /**
   * Delete all authmap entries for a given provider.
   *
   * @param string $provider
   *   The name of the service providing external authentication.
   */
  public function deleteProvider(string $provider);

}
