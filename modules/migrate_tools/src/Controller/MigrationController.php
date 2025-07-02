<?php

declare(strict_types = 1);

namespace Drupal\migrate_tools\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate_plus\Entity\MigrationGroupInterface;
use Drupal\migrate_plus\Entity\MigrationInterface;
use Drupal\migrate_tools\MigrateBatchExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for migrate_tools migration view routes.
 */
class MigrationController extends ControllerBase implements ContainerInjectionInterface {

  protected MigrationPluginManagerInterface $migrationPluginManager;
  protected CurrentRouteMatch $currentRouteMatch;

  /**
   * Constructs a new MigrationController object.
   *
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The plugin manager for config entity-based migrations.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   The current route match.
   */
  public function __construct(MigrationPluginManagerInterface $migration_plugin_manager, CurrentRouteMatch $currentRouteMatch) {
    $this->migrationPluginManager = $migration_plugin_manager;
    $this->currentRouteMatch = $currentRouteMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('plugin.manager.migration'),
      $container->get('current_route_match')
    );
  }

  /**
   * Displays an overview of a migration entity.
   *
   * @param \Drupal\migrate_plus\Entity\MigrationGroupInterface $migration_group
   *   The migration group.
   * @param \Drupal\migrate_plus\Entity\MigrationInterface $migration
   *   The $migration.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function overview(MigrationGroupInterface $migration_group, MigrationInterface $migration): array {
    $build = [];
    $build['overview'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Overview'),
    ];

    $build['overview']['group'] = [
      '#title' => $this->t('Group:'),
      '#markup' => Xss::filterAdmin($migration_group->label()),
      '#type' => 'item',
    ];

    $build['overview']['description'] = [
      '#title' => $this->t('Description:'),
      '#markup' => Xss::filterAdmin($migration->label()),
      '#type' => 'item',
    ];
    $migration_plugin = $this->migrationPluginManager->createInstance($migration->id(), $migration->toArray());
    $migration_dependencies = $migration_plugin->getMigrationDependencies();
    if (!empty($migration_dependencies['required'])) {
      $build['overview']['dependencies'] = [
        '#title' => $this->t('Migration Dependencies') ,
        '#markup' => Xss::filterAdmin(implode(', ', $migration_dependencies['required'])),
        '#type' => 'item',
      ];
    }
    if (!empty($migration_dependencies['optional'])) {
      $build['overview']['soft_dependencies'] = [
        '#title' => $this->t('Soft Migration Dependencies'),
        '#markup' => Xss::filterAdmin(implode(', ', $migration_dependencies['optional'])),
        '#type' => 'item',
      ];
    }

    return $build;
  }

  /**
   * Display source information of a migration entity.
   *
   * @param \Drupal\migrate_plus\Entity\MigrationGroupInterface $migration_group
   *   The migration group.
   * @param \Drupal\migrate_plus\Entity\MigrationInterface $migration
   *   The $migration.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function source(MigrationGroupInterface $migration_group, MigrationInterface $migration): array {
    $build = [];
    // Source field information.
    $build['source'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Source'),
      '#group' => 'detail',
      '#description' => $this->t('<p>These are the fields available from the source of this migration task. The machine names listed here may be used as sources in the process pipeline.</p>'),
      '#description_display' => 'after',
      '#attributes' => [
        'id' => 'migration-detail-source',
      ],
    ];
    $migration_plugin = $this->migrationPluginManager->createInstance($migration->id(), $migration->toArray());
    $source = $migration_plugin->getSourcePlugin();
    $build['source']['query'] = [
      '#type' => 'item',
      '#title' => $this->t('Query'),
      '#markup' => '<pre>' . Xss::filterAdmin($source) . '</pre>',
    ];
    $header = [$this->t('Machine name'), $this->t('Description')];
    $rows = [];
    foreach ($source->fields($migration_plugin) as $machine_name => $description) {
      $rows[] = [
        ['data' => Html::escape($machine_name)],
        ['data' => Xss::filterAdmin($description)],
      ];
    }

    $build['source']['fields'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No fields'),
    ];

    return $build;
  }

  /**
   * Display process information of a migration entity.
   *
   * @param \Drupal\migrate_plus\Entity\MigrationGroupInterface $migration_group
   *   The migration group.
   * @param \Drupal\migrate_plus\Entity\MigrationInterface $migration
   *   The $migration.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function process(MigrationGroupInterface $migration_group, MigrationInterface $migration): array {
    $build = [];
    $migration_plugin = $this->migrationPluginManager->createInstance($migration->id(), $migration->toArray());

    // Process information.
    $build['process'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Process'),
    ];

    $header = [
      $this->t('Destination'),
      $this->t('Source'),
      $this->t('Process plugin'),
      $this->t('Default'),
    ];
    $rows = [];
    foreach ($migration_plugin->getProcess() as $destination_id => $process_line) {
      $row = [];
      $row[] = ['data' => Html::escape($destination_id)];
      if (isset($process_line[0]['source'])) {
        if (is_array($process_line[0]['source'])) {
          $process_line[0]['source'] = implode(', ', $process_line[0]['source']);
        }
        $row[] = ['data' => Xss::filterAdmin($process_line[0]['source'])];
      }
      else {
        $row[] = '';
      }
      if (isset($process_line[0]['plugin'])) {
        $process_line_plugins = [];
        foreach ($process_line as $process_line_row) {
          $process_line_plugins[] = Xss::filterAdmin($process_line_row['plugin']);
        }
        $row[] = ['data' => implode(', ', $process_line_plugins)];
      }
      else {
        $row[] = '';
      }
      if (isset($process_line[0]['default_value'])) {
        $row[] = ['data' => Xss::filterAdmin($process_line[0]['default_value'])];
      }
      else {
        $row[] = '';
      }
      $rows[] = $row;
    }

    $build['process']['fields'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No process defined.'),
    ];

    return $build;
  }

  /**
   * Displays destination information of a migration entity.
   *
   * @param \Drupal\migrate_plus\Entity\MigrationGroupInterface $migration_group
   *   The migration group.
   * @param \Drupal\migrate_plus\Entity\MigrationInterface $migration
   *   The $migration.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function destination(MigrationGroupInterface $migration_group, MigrationInterface $migration): array {
    $build = [];
    $migration_plugin = $this->migrationPluginManager->createInstance($migration->id(), $migration->toArray());

    // Destination field information.
    $build['destination'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Destination'),
      '#group' => 'detail',
      '#description' => $this->t('<p>These are the fields available in the destination plugin of this migration task. The machine names are those available to be used as the keys in the process pipeline.</p>'),
      '#description_display' => 'after',
      '#attributes' => [
        'id' => 'migration-detail-destination',
      ],
    ];
    $destination = $migration_plugin->getDestinationPlugin();
    $build['destination']['type'] = [
      '#type' => 'item',
      '#title' => $this->t('Type'),
      '#markup' => Xss::filterAdmin($destination->getPluginId()),
    ];
    $header = [$this->t('Machine name'), $this->t('Description')];
    $rows = [];
    $destination_fields = $destination->fields() ?: [];
    foreach ($destination_fields as $machine_name => $description) {
      $rows[] = [
        ['data' => Html::escape($machine_name)],
        ['data' => Xss::filterAdmin($description)],
      ];
    }

    $build['destination']['fields'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No fields'),
    ];

    return $build;
  }

}
