<?php

namespace Drupal\config_split\Controller;

use Drupal\config_split\ConfigSplitManager;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Diff\DiffFormatter;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * The controller to view diffs.
 */
class ConfigSplitDiffController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The active storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $activeStorage;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The diff formatter.
   *
   * @var \Drupal\Core\Diff\DiffFormatter
   */
  protected $diffFormatter;

  /**
   * The split manager.
   *
   * @var \Drupal\config_split\ConfigSplitManager
   */
  protected $configSplitManager;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Config\StorageInterface $activeStorage
   *   The active storage.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigManagerInterface $configManager
   *   The config manager.
   * @param \Drupal\Core\Diff\DiffFormatter $diffFormatter
   *   The diff formatter.
   * @param \Drupal\config_split\ConfigSplitManager $configSplitManager
   *   The split manager.
   */
  public function __construct(
    StorageInterface $activeStorage,
    EntityTypeManagerInterface $entityTypeManager,
    ConfigManagerInterface $configManager,
    DiffFormatter $diffFormatter,
    ConfigSplitManager $configSplitManager,
  ) {
    $this->activeStorage = $activeStorage;
    $this->entityTypeManager = $entityTypeManager;
    $this->configManager = $configManager;
    $this->diffFormatter = $diffFormatter;
    $this->configSplitManager = $configSplitManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.storage'),
      $container->get('entity_type.manager'),
      $container->get('config.manager'),
      $container->get('diff.formatter'),
      $container->get('config_split.manager')
    );
  }

  /**
   * Shows diff of specified configuration file.
   *
   * @param string $config_split
   *   The split id.
   * @param string $operation
   *   The operation: deactivate, import, export.
   * @param string $source_name
   *   The name of the configuration file.
   * @param string $target_name
   *   (optional) The name of the target configuration file if different from
   *   the $source_name.
   * @param string $collection
   *   (optional) The configuration collection name. Defaults to the default
   *   collection.
   *
   * @return array|\Symfony\Component\HttpFoundation\Response
   *   Table showing a two-way diff between the active and staged configuration.
   */
  public function diff($config_split, $operation, $source_name, $target_name = NULL, $collection = NULL) {
    $split = $this->configSplitManager->getSplitConfig($config_split);
    if ($split === NULL) {
      return $this->redirect('system.404');
    }
    $headers = [
      'existing' => $this->t('Active'),
      'new' => $this->t('Staged'),
    ];
    // The names for $source_storage and $target_storage are very confusing.
    // But we go with the convention of the diff formatter for now.
    switch ($operation) {
      case 'deactivate':
        $source_storage = $this->activeStorage;
        $target_storage = $this->configSplitManager->singleDeactivate($split, FALSE);
        break;

      case 'activate':
        $source_storage = $this->activeStorage;
        $target_storage = $this->configSplitManager->singleActivate($split, !$split->get('status'));
        break;

      case 'import':
        $source_storage = $this->activeStorage;
        $target_storage = $this->configSplitManager->singleImport($split, !$split->get('status'));
        break;

      case 'export':
        $source_storage = $this->configSplitManager->singleExportTarget($split);
        $target_storage = $this->configSplitManager->singleExportPreview($split);
        $headers = [
          'existing' => $this->t('Existing'),
          'new' => $this->t('New'),
        ];
        break;

      default:
        return $this->redirect('system.404');
    }

    if (!isset($collection)) {
      $collection = StorageInterface::DEFAULT_COLLECTION;
    }

    $diff = $this->configManager->diff($source_storage, $target_storage, $source_name, $target_name, $collection);
    $this->diffFormatter->show_header = FALSE;

    $build = [];

    $build['#title'] = $this->t('View changes of @config_file', ['@config_file' => $source_name]);
    // Add the CSS for the inline diff.
    $build['#attached']['library'][] = 'system/diff';

    $build['diff'] = [
      '#type' => 'table',
      '#attributes' => [
        'class' => ['diff'],
      ],
      '#header' => [
        ['data' => $headers['existing'], 'colspan' => '2'],
        ['data' => $headers['new'], 'colspan' => '2'],
      ],
      '#rows' => $this->diffFormatter->format($diff),
    ];

    $build['back'] = [
      '#type' => 'link',
      '#attributes' => [
        'class' => [
          'dialog-cancel',
        ],
      ],
      '#title' => $this->t("Back to overview."),
      '#url' => Url::fromRoute('config.sync'),
    ];

    return $build;
  }

  /**
   * Returns a redirect response object for the specified route.
   *
   * @param string $route_name
   *   The name of the route to which to redirect.
   * @param array $route_parameters
   *   (optional) Parameters for the route.
   * @param array $options
   *   (optional) An associative array of additional options.
   * @param int $status
   *   (optional) The HTTP redirect status code for the redirect. The default is
   *   302 Found.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response object that may be returned by the controller.
   */
  protected function redirect($route_name, array $route_parameters = [], array $options = [], $status = 302) {
    $options['absolute'] = TRUE;
    return new RedirectResponse(Url::fromRoute($route_name, $route_parameters, $options)->toString(), $status);
  }

}
