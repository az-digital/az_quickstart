<?php

namespace Drupal\webform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for all results exporters.
 */
class WebformPluginExporterController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * A results exporter plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->pluginManager = $container->get('plugin.manager.webform.exporter');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function index() {
    $excluded_exporters = $this->config('webform.settings')->get('export.excluded_exporters');

    $definitions = $this->pluginManager->getDefinitions();
    $definitions = $this->pluginManager->getSortedDefinitions($definitions);

    $rows = [];
    foreach ($definitions as $plugin_id => $definition) {
      $row = [];
      $row[] = $plugin_id;
      $row[] = ['data' => ['#markup' => $definition['label'], '#prefix' => '<span class="webform-form-filter-text-source">', '#suffix' => '</span>']];
      $row[] = $definition['description'];
      $row[] = (isset($excluded_exporters[$plugin_id])) ? $this->t('Yes') : $this->t('No');
      $row[] = $definition['provider'];

      $rows[$plugin_id] = ['data' => $row];
      if (isset($excluded_exporters[$plugin_id])) {
        $rows[$plugin_id]['class'] = ['color-warning'];
      }
    }
    ksort($rows);

    $build = [];

    // Filter.
    $build['filter'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => $this->t('Filter by exporter label'),
      '#attributes' => [
        'class' => ['webform-form-filter-text'],
        'data-element' => '.webform-exporter-plugin-table',
        'data-summary' => '.webform-exporter-plugin-summary',
        'data-item-singlular' => $this->t('exporter'),
        'data-item-plural' => $this->t('exporters'),
        'title' => $this->t('Enter a part of the exporter label to filter by.'),
        'autofocus' => 'autofocus',
      ],
    ];

    // Settings.
    $build['settings'] = [
      '#type' => 'link',
      '#title' => $this->t('Edit configuration'),
      '#url' => Url::fromRoute('webform.config.exporters'),
      '#attributes' => ['class' => ['button', 'button--small'], 'style' => 'float: right'],
    ];

    // Display info.
    $build['info'] = [
      '#markup' => $this->t('@total exporters', ['@total' => count($rows)]),
      '#prefix' => '<p class="webform-exporter-plugin-summary">',
      '#suffix' => '</p>',
    ];

    // Exporters.
    $build['webform_exporters'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('ID'),
        $this->t('Label'),
        $this->t('Description'),
        $this->t('Excluded'),
        $this->t('Provided by'),
      ],
      '#rows' => $rows,
      '#sticky' => TRUE,
      '#attributes' => [
        'class' => ['webform-exporter-plugin-table'],
      ],
    ];

    $build['#attached']['library'][] = 'webform/webform.admin';

    return $build;
  }

}
