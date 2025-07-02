<?php

namespace Drupal\externalauth;

use Drupal\Core\Database\Connection;
use Drupal\user\UserInterface;

/**
 * Service for managing authmap database records.
 */
class Authmap implements AuthmapInterface {

  /**
   * The connection object used for this data.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The connection object used for this data.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function save(UserInterface $account, string $provider, string $authname, $data = NULL) {
    if (!is_scalar($data)) {
      $data = serialize($data);
    }

    // If a mapping (for the same provider) from this authname to a different
    // account already exists, this throws an exception. If a mapping (for the
    // same provider) to this account already exists, the currently stored
    // authname is overwritten.
    $this->connection->merge('authmap')
      ->keys([
        'uid' => $account->id(),
        'provider' => $provider,
      ])
      ->fields([
        'authname' => $authname,
        'data' => $data,
      ])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function get(int $uid, string $provider) {
    $authname = $this->connection->select('authmap', 'am')
      ->fields('am', ['authname'])
      ->condition('uid', $uid)
      ->condition('provider', $provider)
      ->range(0, 1)
      ->execute()
      ->fetchObject();
    if ($authname) {
      return $authname->authname;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthData(int $uid, string $provider) {
    $data = $this->connection->select('authmap', 'am')
      ->fields('am', ['authname', 'data'])
      ->condition('uid', $uid)
      ->condition('provider', $provider)
      ->range(0, 1)
      ->execute()
      ->fetchAssoc();
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getAll($uid): array {
    $query = $this->connection->select('authmap', 'am')
      ->fields('am', ['provider', 'authname'])
      ->condition('uid', $uid)
      ->orderBy('provider', 'ASC')
      ->execute();
    $result = $query->fetchAllAssoc('provider');
    if ($result) {
      foreach ($result as $provider => $data) {
        $result[$provider] = $data->authname;
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getUid(string $authname, string $provider) {
    $authname = $this->connection->select('authmap', 'am')
      ->fields('am', ['uid'])
      ->condition('authname', $authname)
      ->condition('provider', $provider)
      ->range(0, 1)
      ->execute()
      ->fetchObject();
    if ($authname) {
      return $authname->uid;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(int $uid, ?string $provider = NULL) {
    $query = $this->connection->delete('authmap')
      ->condition('uid', $uid);

    if ($provider) {
      $query->condition('provider', $provider);
    }

    $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteProvider(string $provider) {
    $this->connection->delete('authmap')
      ->condition('provider', $provider)
      ->execute();
  }

}
