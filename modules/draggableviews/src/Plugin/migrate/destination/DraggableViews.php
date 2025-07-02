<?php

namespace Drupal\draggableviews\Plugin\migrate\destination;

use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines destination plugin for Draggableviews.
 *
 * @MigrateDestination(
 *   id = "draggableviews"
 * )
 */
class DraggableViews extends DestinationBase implements ContainerFactoryPluginInterface {

  /**
   * The current database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrationInterface $migration = NULL,
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('database'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $record = [
      'view_name' => $row->getDestinationProperty('view_name'),
      'view_display' => $row->getDestinationProperty('view_display'),
      'args' => $row->getDestinationProperty('args'),
      'entity_id' => $row->getDestinationProperty('entity_id'),
      'weight' => $row->getDestinationProperty('weight'),
      'parent' => $row->getDestinationProperty('parent'),
    ];

    $result = $this->database
      ->insert('draggableviews_structure')
      ->fields($record)
      ->execute();

    return [$result];
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    // Delete the specified entity from Drupal if it exists.
    $entity = reset($destination_identifier);
    $this->database
      ->delete('draggableviews_structure')
      ->condition('dvid', $entity)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'dvid' => [
        'type' => 'integer',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [
      'dvid' => $this->t('The primary identifier'),
      'view_name' => $this->t('The view name.'),
      'view_display' => $this->t('The view display.'),
      'args' => $this->t('The arguments.'),
      'entity_id' => $this->t('The entity id.'),
      'weight' => $this->t('The order weight.'),
      'parent' => $this->t('The parent entity id.'),
    ];
  }

}
