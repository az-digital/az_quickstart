<?php

namespace Drupal\config_update_ui\Controller;

use Drupal\config_update\ConfigDiffInterface;
use Drupal\config_update\ConfigListByProviderInterface;
use Drupal\config_update\ConfigRevertInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Diff\DiffFormatter;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Configuration Revert module operations.
 */
class ConfigUpdateController extends ControllerBase {

  /**
   * The config differ.
   *
   * @var \Drupal\config_update\ConfigDiffInterface
   */
  protected $configDiff;

  /**
   * The config lister.
   *
   * @var \Drupal\config_update\ConfigListByProviderInterface
   */
  protected $configList;

  /**
   * The config reverter.
   *
   * @var \Drupal\config_update\ConfigRevertInterface
   */
  protected $configRevert;

  /**
   * The diff formatter.
   *
   * @var \Drupal\Core\Diff\DiffFormatter
   */
  protected $diffFormatter;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Logger interface.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a ConfigUpdateController object.
   *
   * @param \Drupal\config_update\ConfigDiffInterface $config_diff
   *   The config differ.
   * @param \Drupal\config_update\ConfigListByProviderInterface $config_list
   *   The config lister.
   * @param \Drupal\config_update\ConfigRevertInterface $config_update
   *   The config reverter.
   * @param \Drupal\Core\Diff\DiffFormatter $diff_formatter
   *   The diff formatter to use.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger instance.
   */
  public function __construct(ConfigDiffInterface $config_diff, ConfigListByProviderInterface $config_list, ConfigRevertInterface $config_update, DiffFormatter $diff_formatter, ModuleExtensionList $module_extension_list, ThemeHandlerInterface $theme_handler, ConfigFactoryInterface $config_factory, LoggerInterface $logger) {
    $this->configDiff = $config_diff;
    $this->configList = $config_list;
    $this->configRevert = $config_update;
    $this->diffFormatter = $diff_formatter;
    $this->diffFormatter->show_header = FALSE;
    $this->moduleExtensionList = $module_extension_list;
    $this->themeHandler = $theme_handler;
    $this->configFactory = $config_factory;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config_update.config_diff'),
      $container->get('config_update.config_list'),
      $container->get('config_update.config_update'),
      $container->get('diff.formatter'),
      $container->get('extension.list.module'),
      $container->get('theme_handler'),
      $container->get('config.factory'),
      $container->get('logger.factory')->get('config_update_ui')
    );
  }

  /**
   * Shows the diff between active and provided configuration.
   *
   * @param string $config_type
   *   The type of configuration.
   * @param string $config_name
   *   The name of the config item, without the prefix.
   *
   * @return array
   *   Render array for page showing differences between them.
   */
  public function diff($config_type, $config_name) {
    $diff = $this->configDiff->diff(
      $this->configRevert->getFromExtension($config_type, $config_name),
      $this->configRevert->getFromActive($config_type, $config_name)
    );

    $build = [];
    $definition = $this->configList->getType($config_type);
    $config_type_label = ($definition) ? $definition->getLabel() : $this->t('Simple configuration');
    $build['#title'] = $this->t('Config difference for @type @name',
      [
        '@type' => $config_type_label,
        '@name' => $config_name,
      ]
    );
    $build['#attached']['library'][] = 'system/diff';

    $rows = $this->diffFormatter->format($diff);
    $build['diff'] = [
      '#type' => 'table',
      '#header' => [
        ['data' => $this->t('Source config'), 'colspan' => '2'],
        ['data' => $this->t('Site config'), 'colspan' => '2'],
      ],
      '#rows' => $rows,
      '#empty' => $this->t('There are no changes.'),
      '#attributes' => ['class' => ['diff']],
    ];

    $url = new Url('config_update_ui.report');

    $build['wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['form-actions'],
      ],
    ];
    $links = [];
    if (!empty($rows)) {
      $links['revert'] = [
        'url' => Url::fromRoute('config_update_ui.revert',
          [
            'config_type' => $config_type,
            'config_name' => $config_name,
          ]
        ),
        'title' => $this->t('Revert to source'),
      ];
    }
    $links['export'] = [
      'url' => Url::fromRoute('config.export_single', ['config_type' => $config_type, 'config_name' => $config_name]),
      'title' => $this->t('Export'),
    ];
    $links['delete'] = [
      'url' => Url::fromRoute('config_update_ui.delete', ['config_type' => $config_type, 'config_name' => $config_name]),
      'title' => $this->t('Delete'),
    ];
    $build['wrapper']['operations'] = [
      '#type' => 'dropbutton',
      '#links' => $links,
    ];

    $build['wrapper']['back'] = [
      '#type' => 'link',
      '#attributes' => [
        'class' => [
          'button',
        ],
      ],
      '#title' => $this->t("Back to 'Updates report' page"),
      '#url' => $url,
    ];

    return $build;
  }

  /**
   * Generates the config updates report.
   *
   * @param string $report_type
   *   (optional) Type of report to run:
   *   - type: Configuration entity type.
   *   - module: Module.
   *   - theme: Theme.
   *   - profile: Install profile.
   * @param string $name
   *   (optional) Name of specific item to run report for (config entity type
   *   ID, module machine name, etc.). Ignored for profile.
   *
   * @return array
   *   Render array for report, with section at the top for selecting another
   *   report to run. If either $report_type or $name is missing, the report
   *   itself is not generated.
   */
  public function report($report_type = NULL, $name = NULL) {
    $links = $this->generateReportLinks();

    $report = $this->generateReport($report_type, $name);
    if (!$report) {
      return $links;
    }

    // If there is a report, extract the title, put table of links in a
    // details element, and add report to build.
    $build = [];
    $build['#title'] = $report['#title'];
    unset($report['#title']);

    $build['links_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Generate new report'),
      '#children' => $links,
    ];

    $build['report'] = $report;

    $build['#attached']['library'][] = 'config_update/report_css';

    return $build;
  }

  /**
   * Generates the operations links for running individual reports.
   *
   * @return array
   *   Render array for the operations links for running reports.
   */
  protected function generateReportLinks() {

    // These links are put into an 'operations' render array element. They do
    // not look good outside of tables. Also note that the array index in
    // operations links is used as a class on the LI element. Some classes are
    // special in the Seven CSS, such as "contextual", so avoid hitting these
    // accidentally by prefixing.
    $build = [];

    $build['links'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Report type'),
        $this->t('Report on'),
      ],
      '#rows' => [],
    ];

    // Full report of all configuration.
    $links['report_full'] = [
      'title' => $this->t('Everything'),
      'url' => Url::fromRoute('config_update_ui.report', ['report_type' => 'type', 'name' => 'system.all']),
    ];
    $build['links']['#rows'][] = [
      $this->t('Full report'),
      [
        'data' => [
          '#type' => 'operations',
          '#links' => $links,
        ],
      ],
    ];

    // Reports by configuration type.
    $definitions = $this->configList->listTypes();
    $links = [];
    foreach ($definitions as $entity_type => $definition) {
      $links['report_type_' . $entity_type] = [
        'title' => $definition->getLabel(),
        'url' => Url::fromRoute('config_update_ui.report', ['report_type' => 'type', 'name' => $entity_type]),
      ];
    }

    uasort($links, [$this, 'sortLinks']);

    $links = [
      'report_type_system.simple' => [
        'title' => $this->t('Simple configuration'),
        'url' => Url::fromRoute('config_update_ui.report', ['report_type' => 'type', 'name' => 'system.simple']),
      ],
    ] + $links;

    $build['links']['#rows'][] = [
      $this->t('Single configuration type'),
      [
        'data' => [
          '#type' => 'operations',
          '#links' => $links,
        ],
      ],
    ];

    // Make a list of installed modules.
    $profile = $this->getProfileName();
    $modules = $this->moduleExtensionList->getList();
    $links = [];
    foreach ($modules as $machine_name => $module) {
      if ($machine_name != $profile && $this->configList->providerHasConfig('module', $machine_name)) {
        $links['report_module_' . $machine_name] = [
          'title' => $this->moduleExtensionList->getName($machine_name),
          'url' => Url::fromRoute('config_update_ui.report', ['report_type' => 'module', 'name' => $machine_name]),
        ];
      }
    }
    uasort($links, [$this, 'sortLinks']);

    $build['links']['#rows'][] = [
      $this->t('Single module'),
      [
        'data' => [
          '#type' => 'operations',
          '#links' => $links,
        ],
      ],
    ];

    // Make a list of installed themes.
    $themes = $this->themeHandler->listInfo();
    $links = [];
    foreach ($themes as $machine_name => $theme) {
      if ($this->configList->providerHasConfig('theme', $machine_name)) {
        $links['report_theme_' . $machine_name] = [
          'title' => $this->themeHandler->getName($machine_name),
          'url' => Url::fromRoute('config_update_ui.report', ['report_type' => 'theme', 'name' => $machine_name]),
        ];
      }
    }
    uasort($links, [$this, 'sortLinks']);

    $build['links']['#rows'][] = [
      $this->t('Single theme'),
      [
        'data' => [
          '#type' => 'operations',
          '#links' => $links,
        ],
      ],
    ];

    $links = [];

    // Profile is just one option.
    if ($this->configList->providerHasConfig('profile', $profile)) {
      $links['report_profile_' . $profile] = [
        'title' => $this->moduleExtensionList->getName($profile),
        'url' => Url::fromRoute('config_update_ui.report', ['report_type' => 'profile']),
      ];
      $build['links']['#rows'][] = [
        $this->t('Installation profile'),
        [
          'data' => [
            '#type' => 'operations',
            '#links' => $links,
          ],
        ],
      ];
    }

    return $build;
  }

  /**
   * Generates a report about config updates.
   *
   * @param string $report_type
   *   Type of report to generate: 'type', 'module', 'theme', or 'profile'.
   * @param string $value
   *   Machine name of a configuration type, module, or theme to generate the
   *   report for. Ignored for profile, since that uses the active profile.
   *
   * @return array
   *   Render array for the updates report. Empty if invalid or missing
   *   report type or value.
   */
  protected function generateReport($report_type, $value) {
    // Figure out what to name the report, and incidentally, validate that
    // $value exists for this type of report.
    switch ($report_type) {
      case 'type':
        if ($value == 'system.all') {
          $label = $this->t('All configuration');
        }
        elseif ($value == 'system.simple') {
          $label = $this->t('Simple configuration');
        }
        else {
          $definition = $this->configList->getType($value);
          if (!$definition) {
            return NULL;
          }

          $label = $this->t('@name configuration', ['@name' => $definition->getLabel()]);
        }

        break;

      case 'module':
        $list = $this->moduleExtensionList->getList();
        if (!isset($list[$value])) {
          return NULL;
        }

        $label = $this->t('@name module', ['@name' => $this->moduleExtensionList->getName($value)]);
        break;

      case 'theme':
        $list = $this->themeHandler->listInfo();
        if (!isset($list[$value])) {
          return NULL;
        }

        $label = $this->t('@name theme', ['@name' => $this->themeHandler->getName($value)]);
        break;

      case 'profile':
        $profile = $this->getProfileName();
        $label = $this->t('@name profile', ['@name' => $this->moduleExtensionList->getName($profile)]);
        break;

      default:
        return NULL;
    }

    // List the active and extension-provided config.
    [$active_list, $install_list, $optional_list] = $this->configList->listConfig($report_type, $value);

    // Build the report.
    $build = [];

    $build['#title'] = $this->t('Configuration updates report for @label', ['@label' => $label]);
    $build['report_header'] = ['#markup' => '<h3>' . $this->t('Updates report') . '</h3>'];

    // List items missing from site.
    $removed = array_diff($install_list, $active_list);
    $build['removed'] = [
      '#caption' => $this->t('Missing configuration items'),
      '#empty' => $this->t('None: all provided configuration items are in your active configuration.'),
    ] + $this->makeReportTable($removed, 'extension', ['import']);

    // List optional items that are not installed.
    $inactive = array_diff($optional_list, $active_list);
    $build['inactive'] = [
      '#caption' => $this->t('Inactive optional items'),
      '#empty' => $this->t('None: all optional configuration items are in your active configuration.'),
    ] + $this->makeReportTable($inactive, 'extension', ['import']);

    // List items added to site, which only makes sense in the report for a
    // config type.
    $added = array_diff($active_list, $install_list, $optional_list);
    if ($report_type == 'type') {
      $build['added'] = [
        '#caption' => $this->t('Added configuration items'),
        '#empty' => $this->t('None: all active configuration items of this type were provided by modules, themes, or install profile.'),
      ] + $this->makeReportTable($added, 'active', ['export', 'delete']);
    }

    // For differences, we need to go through the array of config in both
    // and see if each config item is the same or not.
    $both = array_diff($active_list, $added);
    $different = [];
    foreach ($both as $name) {
      if (!$this->configDiff->same(
        $this->configRevert->getFromExtension('', $name),
        $this->configRevert->getFromActive('', $name)
      )) {
        $different[] = $name;
      }
    }
    $build['different'] = [
      '#caption' => $this->t('Changed configuration items'),
      '#empty' => $this->t('None: no active configuration items differ from their current provided versions.'),
    ] + $this->makeReportTable($different, 'active',
      ['diff', 'export', 'revert', 'delete']);

    return $build;
  }

  /**
   * Builds a table for the report.
   *
   * @param string[] $names
   *   List of machine names of config items for the table.
   * @param string $storage
   *   Config storage the items can be loaded from, either 'active' or
   *   'extension'.
   * @param string[] $actions
   *   Action links to include, one or more of:
   *   - diff
   *   - revert
   *   - export
   *   - import
   *   - delete.
   *
   * @return array
   *   Render array for the table, not including the #empty and #prefix
   *   properties.
   */
  protected function makeReportTable(array $names, $storage, array $actions) {
    $build = [];

    $ignored_items = $this->configFactory->get('config_update_ui.settings')->get('ignore');
    if (is_array($ignored_items)) {
      $names = array_diff($names, $ignored_items);
    }

    $build['#type'] = 'table';

    $build['#attributes'] = ['class' => ['config-update-report']];

    $build['#header'] = [
      'name' => [
        'data' => $this->t('Machine name'),
      ],
      'label' => [
        'data' => $this->t('Label (if any)'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'type' => [
        'data' => $this->t('Type'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'provider' => [
        'data' => $this->t('Provider'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'operations' => [
        'data' => $this->t('Operations'),
      ],
    ];

    $build['#rows'] = [];

    sort($names);

    foreach ($names as $name) {
      $row = [];
      if ($storage == 'active') {
        $config = $this->configRevert->getFromActive('', $name);
      }
      else {
        $config = $this->configRevert->getFromExtension('', $name);
      }

      if (empty($config)) {
        $this->logger->error('Malformed config file @config_name.', ['@config_name' => $name]);
        continue;
      }

      // Figure out what type of config it is, and get the ID.
      $entity_type = $this->configList->getTypeNameByConfigName($name);

      if (!$entity_type) {
        // This is simple config.
        $id = $name;
        $label = '';
        $type_label = $this->t('Simple configuration');
        $entity_type = 'system.simple';
      }
      else {
        $definition = $this->configList->getType($entity_type);
        $id_key = $definition->getKey('id');
        $id = $config[$id_key];
        // The label key is not required.
        if ($label_key = $definition->getKey('label')) {
          $label = $config[$label_key];
        }
        else {
          $label = '';
        }

        $type_label = $definition->getLabel();
      }

      $row[] = $name;
      $row[] = $label;
      $row[] = $type_label;
      $provider = $this->configList->getConfigProvider($name);
      $provider_name = '';
      if (!empty($provider)) {
        switch ($provider[0]) {
          case 'profile':
            $provider_name = $this->moduleExtensionList->getName($provider[1]);
            if ($provider_name) {
              $provider_name = $this->t('@name profile', ['@name' => $provider_name]);
            }
            else {
              $provider_name = '';
            }
            break;

          case 'module':
            $provider_name = $this->moduleExtensionList->getName($provider[1]);
            if ($provider_name) {
              $provider_name = $this->t('@name module', ['@name' => $provider_name]);
            }
            else {
              $provider_name = '';
            }
            break;

          case 'theme':
            $provider_name = $this->themeHandler->getName($provider[1]);
            if ($provider_name) {
              $provider_name = $this->t('@name theme', ['@name' => $provider_name]);
            }
            else {
              $provider_name = '';
            }
            break;
        }
      }
      $row[] = $provider_name;

      $links = [];
      $routes = [
        'export' => 'config.export_single',
        'import' => 'config_update_ui.import',
        'diff' => 'config_update_ui.diff',
        'revert' => 'config_update_ui.revert',
        'delete' => 'config_update_ui.delete',
      ];
      $titles = [
        'export' => $this->t('Export'),
        'import' => $this->t('Import from source'),
        'diff' => $this->t('Show differences'),
        'revert' => $this->t('Revert to source'),
        'delete' => $this->t('Delete'),
      ];

      foreach ($actions as $action) {
        $links[$action] = [
          'url' => Url::fromRoute($routes[$action], ['config_type' => $entity_type, 'config_name' => $id]),
          'title' => $titles[$action],
        ];
      }

      $row[] = [
        'data' => [
          '#type' => 'operations',
          '#links' => $links,
        ],
      ];

      $build['#rows'][] = $row;
    }

    return $build;
  }

  /**
   * Compares links for uasort(), to sort by displayed link title.
   */
  protected static function sortLinks($link1, $link2) {
    $title1 = $link1['title'];
    $title2 = $link2['title'];
    if ($title1 == $title2) {
      return 0;
    }
    return ($title1 < $title2) ? -1 : 1;
  }

  /**
   * Returns the name of the install profile.
   *
   * For backwards compatibility with pre/post 8.3.x, tries to get it from
   * either configuration or settings.
   *
   * @return string
   *   The name of the install profile.
   */
  protected function getProfileName() {
    // Code adapted from DrupalKernel::getInstalProfile() in Core.
    // In Core 8.3.x or later, read from config.
    $profile = $this->configFactory->get('core.extension')->get('profile');
    if (!empty($profile)) {
      return $profile;
    }
    else {
      // If system_update_8300() has not yet run, use settings.
      return Settings::get('install_profile');
    }
  }

}
