<?php

namespace Drupal\webform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Plugin\WebformHandlerInterface;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for all webform handlers.
 */
class WebformPluginHandlerController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The webform handler plugin manager.
   *
   * @var \Drupal\webform\Plugin\WebformHandlerManagerInterface
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->pluginManager = $container->get('plugin.manager.webform.handler');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function index() {
    $excluded_handlers = $this->config('webform.settings')->get('handler.excluded_handlers');

    $used_by = [];
    /** @var \Drupal\webform\WebformInterface[] $webforms */
    $webforms = Webform::loadMultiple();
    foreach ($webforms as $webform) {
      $handlers = $webform->getHandlers();
      foreach ($handlers as $handler) {
        $used_by[$handler->getPluginId()][$webform->id()] = $webform->toLink()->toRenderable();
      }
    }

    $definitions = $this->pluginManager->getDefinitions();
    $definitions = $this->pluginManager->getSortedDefinitions($definitions);

    $rows = [];
    foreach ($definitions as $plugin_id => $definition) {
      $row = [];
      $row[] = $plugin_id;
      $row[] = ['data' => ['#markup' => $definition['label'], '#prefix' => '<span class="webform-form-filter-text-source">', '#suffix' => '</span>']];
      $row[] = $definition['description'];
      $row[] = $definition['category'];
      $row[] = (isset($excluded_handlers[$plugin_id])) ? $this->t('Yes') : $this->t('No');
      $row[] = ($definition['cardinality'] === -1) ? $this->t('Unlimited') : $definition['cardinality'];
      $row[] = $definition['conditions'] ? $this->t('Yes') : $this->t('No');
      $row[] = $definition['submission'] ? $this->t('Required') : $this->t('Optional');
      $row[] = $definition['results'] ? $this->t('Processed') : $this->t('Ignored');
      $row[] = (isset($used_by[$plugin_id])) ? ['data' => ['#theme' => 'item_list', '#items' => $used_by[$plugin_id]]] : '';
      $row[] = $definition['provider'];
      $rows[$plugin_id] = ['data' => $row];
      if (isset($excluded_handlers[$plugin_id])) {
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
      '#placeholder' => $this->t('Filter by handler label'),
      '#attributes' => [
        'class' => ['webform-form-filter-text'],
        'data-element' => '.webform-handler-plugin-table',
        'data-summary' => '.webform-handler-plugin-summary',
        'data-item-singlular' => $this->t('handler'),
        'data-item-plural' => $this->t('handlers'),
        'title' => $this->t('Enter a part of the handler label to filter by.'),
        'autofocus' => 'autofocus',
      ],
    ];

    // Settings.
    $build['settings'] = [
      '#type' => 'link',
      '#title' => $this->t('Edit configuration'),
      '#url' => Url::fromRoute('webform.config.handlers'),
      '#attributes' => ['class' => ['button', 'button--small'], 'style' => 'float: right'],
    ];

    // Display info.
    $build['info'] = [
      '#markup' => $this->t('@total handlers', ['@total' => count($rows)]),
      '#prefix' => '<p class="webform-handler-plugin-summary">',
      '#suffix' => '</p>',
    ];

    // Handlers.
    $build['webform_handlers'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('ID'),
        $this->t('Label'),
        $this->t('Description'),
        $this->t('Category'),
        $this->t('Excluded'),
        $this->t('Cardinality'),
        $this->t('Conditional'),
        $this->t('Database'),
        $this->t('Results'),
        $this->t('Used by'),
        $this->t('Provided by'),
      ],
      '#rows' => $rows,
      '#sticky' => TRUE,
      '#attributes' => [
        'class' => ['webform-handler-plugin-table'],
      ],
    ];

    $build['#attached']['library'][] = 'webform/webform.admin';

    return $build;
  }

  /**
   * Shows a list of webform handlers that can be added to a webform.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return array
   *   A render array as expected by the renderer.
   */
  public function listHandlers(Request $request, WebformInterface $webform) {
    $headers = [
      ['data' => $this->t('Handler'), 'width' => '20%'],
      ['data' => $this->t('Description'), 'width' => '40%'],
      ['data' => $this->t('Category'), 'width' => '20%'],
      ['data' => $this->t('Operations'), 'width' => '20%'],
    ];

    $definitions = $this->pluginManager->getDefinitions();
    $definitions = $this->pluginManager->getSortedDefinitions($definitions);
    $definitions = $this->pluginManager->removeExcludeDefinitions($definitions);

    $rows = [];
    foreach ($definitions as $plugin_id => $definition) {
      // Skip email handler which has dedicated button.
      if ($plugin_id === 'email') {
        continue;
      }
      /** @var \Drupal\webform\Plugin\WebformHandlerInterface $handler_plugin */
      $handler_plugin = $this->pluginManager->createInstance($plugin_id);

      // Check if applicable.
      if (!$handler_plugin->isApplicable($webform)) {
        continue;
      }

      // Check cardinality.
      $cardinality = $definition['cardinality'];
      $is_cardinality_unlimited = ($cardinality === WebformHandlerInterface::CARDINALITY_UNLIMITED);
      $is_cardinality_reached = ($webform->getHandlers($plugin_id)->count() >= $cardinality);
      if (!$is_cardinality_unlimited && $is_cardinality_reached) {
        continue;
      }

      $is_submission_required = ($definition['submission'] === WebformHandlerInterface::SUBMISSION_REQUIRED);
      $is_results_disabled = $webform->getSetting('results_disabled');

      $row = [];

      if ($is_submission_required && $is_results_disabled) {
        $row['title']['data'] = [
          '#markup' => $definition['label'],
          '#prefix' => '<div class="webform-form-filter-text-source">',
          '#suffix' => '</div>',
        ];
      }
      else {
        $row['title']['data'] = [
          '#type' => 'link',
          '#title' => $definition['label'],
          '#url' => Url::fromRoute('entity.webform.handler.add_form', ['webform' => $webform->id(), 'webform_handler' => $plugin_id]),
          '#attributes' => WebformDialogHelper::getOffCanvasDialogAttributes($handler_plugin->getOffCanvasWidth()),
          '#prefix' => '<div class="webform-form-filter-text-source">',
          '#suffix' => '</div>',
        ];
      }

      $row['description'] = [
        'data' => [
          '#markup' => $definition['description'],
        ],
      ];

      $row['category'] = $definition['category'];

      // Check submission required.
      if ($is_submission_required && $is_results_disabled) {
        $row['operations']['data'] = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $this->t('Requires saving of submissions.'),
          '#attributes' => ['class' => ['color-warning']],
        ];
      }
      else {
        $links['add'] = [
          'title' => $this->t('Add handler'),
          'url' => Url::fromRoute('entity.webform.handler.add_form', ['webform' => $webform->id(), 'webform_handler' => $plugin_id]),
          'attributes' => WebformDialogHelper::getOffCanvasDialogAttributes($handler_plugin->getOffCanvasWidth()),
        ];
        $row['operations']['data'] = [
          '#type' => 'operations',
          '#links' => $links,
          '#prefix' => '<div class="webform-dropbutton">',
          '#suffix' => '</div>',
        ];
      }

      $rows[] = $row;
    }

    $build['filter'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => $this->t('Filter by handler name'),
      '#attributes' => [
        'class' => ['webform-form-filter-text'],
        'data-element' => '.webform-handler-add-table',
        'data-item-singlular' => $this->t('handler'),
        'data-item-plural' => $this->t('handlers'),
        'title' => $this->t('Enter a part of the handler name to filter by.'),
        'autofocus' => 'autofocus',
      ],
    ];

    $build['handlers'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#sticky' => TRUE,
      '#empty' => $this->t('No handler available.'),
      '#attributes' => [
        'class' => ['webform-handler-add-table'],
      ],
    ];

    $build['#attached']['library'][] = 'webform/webform.admin';

    return $build;
  }

}
