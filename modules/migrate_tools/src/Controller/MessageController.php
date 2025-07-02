<?php

declare(strict_types = 1);

namespace Drupal\migrate_tools\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Database\Query\TableSortExtender;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate_plus\Entity\MigrationGroupInterface;
use Drupal\migrate_plus\Entity\MigrationInterface as MigratePlusMigrationInterface;
use Drupal\migrate_tools\MigrateTools;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for migrate_tools message routes.
 */
class MessageController extends ControllerBase {

  protected Connection $database;
  protected MigrationPluginManagerInterface $migrationPluginManager;

  /**
   * Constructs a MessageController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The migration plugin manager.
   */
  public function __construct(Connection $database, MigrationPluginManagerInterface $migration_plugin_manager) {
    $this->database = $database;
    $this->migrationPluginManager = $migration_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('database'),
      $container->get('plugin.manager.migration')
    );
  }

  /**
   * Gets an array of log level classes.
   *
   * @return array
   *   An array of log level classes.
   */
  public static function getLogLevelClassMap(): array {
    return [
      MigrationInterface::MESSAGE_INFORMATIONAL => 'migrate-message-4',
      MigrationInterface::MESSAGE_NOTICE => 'migrate-message-3',
      MigrationInterface::MESSAGE_WARNING => 'migrate-message-2',
      MigrationInterface::MESSAGE_ERROR => 'migrate-message-1',
    ];
  }

  /**
   * Displays a listing of migration messages.
   *
   * Messages are truncated at 56 chars.
   *
   * @param \Drupal\migrate_plus\Entity\MigrationGroupInterface $migration_group
   *   The migration group.
   * @param \Drupal\migrate_plus\Entity\MigrationInterface $migration
   *   The $migration.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function overview(MigrationGroupInterface $migration_group, MigratePlusMigrationInterface $migration): array {
    $header = [];
    $build = [];
    $rows = [];
    $classes = static::getLogLevelClassMap();
    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration_plugin */
    $migration_plugin = $this->migrationPluginManager->createInstance($migration->id(), $migration->toArray());
    $source_id_field_names = array_keys($migration_plugin->getSourcePlugin()->getIds());
    $column_number = 1;
    foreach ($source_id_field_names as $source_id_field_name) {
      $header[] = [
        'data' => $source_id_field_name,
        'field' => 'sourceid' . $column_number++,
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ];
    }
    $header[] = [
      'data' => $this->t('Severity level'),
      'field' => 'level',
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    $header[] = [
      'data' => $this->t('Message'),
      'field' => 'message',
    ];
    $header[] = [
      'data' => $this->t('Destination ID'),
      'field' => 'destid',
    ];
    $header[] = [
      'data' => $this->t('Status'),
      'field' => 'source_row_status',
    ];

    $result = [];
    $message_table = $migration_plugin->getIdMap()->messageTableName();
    if ($this->database->schema()->tableExists($message_table)) {
      $map_table = $migration_plugin->getIdMap()->mapTableName();
      $query = $this->database->select($message_table, 'msg')
        ->extend(PagerSelectExtender::class)
        ->extend(TableSortExtender::class);
      $query->innerJoin($map_table, 'map', 'msg.source_ids_hash=map.source_ids_hash');
      $query->fields('msg');
      $query->fields('map');
      $result = $query
        ->limit(50)
        ->orderByHeader($header)
        ->execute();
    }

    $level_mapping = MigrateTools::getLogLevelLabelMapping();
    $status_mapping = MigrateTools::getStatusLevelLabelMapping();

    foreach ($result as $message_row) {
      $column_number = 1;
      $data = [];
      foreach ($source_id_field_names as $source_id_field_name) {
        $column_name = 'sourceid' . $column_number++;
        $data[$column_name] = $message_row->$column_name;
      }
      $data['level'] = $level_mapping[$message_row->level] ?: $message_row->level;
      $data['message'] = $message_row->message;
      $column_number = 1;
      foreach ($migration_plugin->getDestinationPlugin()->getIds() as $dest_id_field_name => $dest_id_schema) {
        $column_name = 'destid' . $column_number++;
        $data['destid']['data'][] = $message_row->$column_name;
        $data['destid']['#destination_fields'][$dest_id_field_name] =
        $data['destid']['#destination_fields'][$column_name] = $message_row->$column_name;
      }
      $destid = array_filter($data['destid']['data']);
      $data['destid']['data'] = [
        '#markup' => $destid ? implode(MigrateTools::DEFAULT_ID_LIST_DELIMITER, $data['destid']['data']) : '',
      ];

      $data['status'] = $status_mapping[$message_row->source_row_status];
      $rows[] = [
        'class' => [
          Html::getClass('migrate-message-' . $message_row->level),
          $classes[$message_row->level],
        ],
        'data' => $data,
      ];
    }

    $build['message_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => ['id' => $message_table, 'class' => [$message_table]],
      '#empty' => $this->t('No messages for this migration.'),
    ];
    $build['message_pager'] = ['#type' => 'pager'];

    return $build;
  }

  /**
   * Get the title of the page.
   *
   * @param \Drupal\migrate_plus\Entity\MigrationGroupInterface $migration_group
   *   The migration group.
   * @param \Drupal\migrate_plus\Entity\MigrationInterface $migration
   *   The $migration.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The translated title.
   */
  public function title(MigrationGroupInterface $migration_group, MigratePlusMigrationInterface $migration): TranslatableMarkup {
    return $this->t(
      'Messages of %migration',
      ['%migration' => $migration->label()]
    );
  }

}
