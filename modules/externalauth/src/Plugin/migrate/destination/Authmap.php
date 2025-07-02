<?php

namespace Drupal\externalauth\Plugin\migrate\destination;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\externalauth\AuthmapInterface;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Drupal 8 authmap destination.
 *
 * @MigrateDestination(
 *   id = "authmap"
 * )
 */
class Authmap extends DestinationBase implements ContainerFactoryPluginInterface {

  /**
   * The Authmap class.
   *
   * @var \Drupal\externalauth\AuthmapInterface
   */
  protected $authmap;

  /**
   * User storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * Constructs an entity destination plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration.
   * @param \Drupal\externalauth\AuthmapInterface $authmap
   *   The Authmap handling class.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, AuthmapInterface $authmap, UserStorageInterface $user_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->authmap = $authmap;
    $this->userStorage = $user_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('externalauth.authmap'),
      $container->get('entity_type.manager')->getStorage('user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds(): array {
    return [
      'uid' => [
        'type' => 'integer',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields(?MigrationInterface $migration = NULL): array {
    return [
      'uid' => 'Primary key: users.uid for user.',
      'provider' => 'The name of the authentication provider providing the authname',
      'authname' => 'Unique authentication name provided by authentication provider',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []): array {
    /** @var \Drupal\user\UserInterface $account */
    $account = $this->userStorage->load($row->getDestinationProperty('uid'));
    $provider = $row->getDestinationProperty('provider');
    $authname = $row->getDestinationProperty('authname');
    $this->authmap->save($account, $provider, $authname);

    return [$account->id()];
  }

}
