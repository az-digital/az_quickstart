<?php

namespace Drupal\upgrade_status\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DrupalKernelInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\upgrade_status\CookieJar;
use Drupal\upgrade_status\DeprecationAnalyzer;
use Drupal\upgrade_status\ProjectCollector;
use Drupal\upgrade_status\ScanResultFormatter;
use Drupal\user\Entity\Role;
use GuzzleHttp\Cookie\SetCookie;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;

class UpgradeStatusForm extends FormBase {

  /**
   * The project collector service.
   *
   * @var \Drupal\upgrade_status\ProjectCollector
   */
  protected $projectCollector;

  /**
   * Available releases store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface|mixed
   */
  protected $releaseStore;

  /**
   * The scan result formatter service.
   *
   * @var \Drupal\upgrade_status\ScanResultFormatter
   */
  protected $resultFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The deprecation analyzer.
   *
   * @var \Drupal\upgrade_status\DeprecationAnalyzer
   */
  protected $deprecationAnalyzer;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $destination;

  /**
   * The next Drupal core major version.
   *
   * @var int
   */
  protected $nextMajor;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Drupal kernel.
   *
   * @var \Drupal\Core\DrupalKernelInterface
   */
  protected $kernel;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('upgrade_status.project_collector'),
      $container->get('keyvalue.expirable'),
      $container->get('upgrade_status.result_formatter'),
      $container->get('renderer'),
      $container->get('logger.channel.upgrade_status'),
      $container->get('module_handler'),
      $container->get('upgrade_status.deprecation_analyzer'),
      $container->get('state'),
      $container->get('date.formatter'),
      $container->get('redirect.destination'),
      $container->get('database'),
      $container->get('kernel')
    );
  }

  /**
   * Constructs a Drupal\upgrade_status\Form\UpgradeStatusForm.
   *
   * @param \Drupal\upgrade_status\ProjectCollector $project_collector
   *   The project collector service.
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $key_value_expirable
   *   The expirable key/value storage.
   * @param \Drupal\upgrade_status\ScanResultFormatter $result_formatter
   *   The scan result formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\upgrade_status\DeprecationAnalyzer $deprecation_analyzer
   *   The deprecation analyzer.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   * @param  \Drupal\Core\Routing\RedirectDestinationInterface $destination
   *   The destination service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\DrupalKernelInterface $kernel
   *   The Drupal kernel.
   */
  public function __construct(
    ProjectCollector $project_collector,
    KeyValueExpirableFactoryInterface $key_value_expirable,
    ScanResultFormatter $result_formatter,
    RendererInterface $renderer,
    LoggerInterface $logger,
    ModuleHandlerInterface $module_handler,
    DeprecationAnalyzer $deprecation_analyzer,
    StateInterface $state,
    DateFormatterInterface $date_formatter,
    RedirectDestinationInterface $destination,
    Connection $database,
    DrupalKernelInterface $kernel
  ) {
    $this->projectCollector = $project_collector;
    $this->releaseStore = $key_value_expirable->get('update_available_releases');
    $this->resultFormatter = $result_formatter;
    $this->renderer = $renderer;
    $this->logger = $logger;
    $this->moduleHandler = $module_handler;
    $this->deprecationAnalyzer = $deprecation_analyzer;
    $this->state = $state;
    $this->dateFormatter = $date_formatter;
    $this->destination = $destination;
    $this->nextMajor = ProjectCollector::getDrupalCoreMajorVersion() + 1;
    $this->database = $database;
    $this->kernel = $kernel;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'drupal_upgrade_status_summary_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'upgrade_status/upgrade_status.admin';

    $analyzer_ready = TRUE;
    try {
      $this->deprecationAnalyzer->initEnvironment();
    }
    catch (\Exception $e) {
      $analyzer_ready = FALSE;
      // Message and impact description is not translated as the message
      // is sourced from an exception thrown. Adding it to both the set
      // of standard Drupal messages and to the bottom around the buttons.
      $this->messenger()->addError($e->getMessage() . ' Scanning is not possible until this is resolved.');
      $form['warning'] = [
        [
          '#theme' => 'status_messages',
          '#message_list' => [
            'error' => [$e->getMessage() . ' Scanning is not possible until this is resolved.'],
          ],
          '#status_headings' => [
            'error' => t('Error message'),
          ],
        ],
        // Set weight lower than the "actions" element's 100.
        '#weight' => 90,
      ];
    }

    if ($this->nextMajor == 12) {
      $environment = $this->buildEnvironmentChecksFor12();
    }
    elseif ($this->nextMajor == 11) {
      $environment = $this->buildEnvironmentChecksFor11();
    }
    else {
      $environment = $this->buildEnvironmentChecksFor10();
    }
    $form['summary'] = $this->buildResultSummary($environment['status']);
    $environment_description = $environment['description'];
    unset($environment['status']);
    unset($environment['description']);

    $form['environment'] = [
      '#type' => 'details',
      '#title' => $this->t('Drupal core and hosting environment'),
      '#description' => $environment_description,
      '#open' => TRUE,
      '#attributes' => ['class' => ['upgrade-status-of-environment']],
      'data' => $environment,
      '#tree' => TRUE,
    ];

    // Gather project list with metadata.
    $projects = $this->projectCollector->collectProjects();
    $next_steps = $this->projectCollector->getNextStepInfo();
    foreach ($next_steps as $next_step => $step_label) {
      $sublist = [];
      foreach ($projects as $name => $project) {
        if ($project->info['upgrade_status_next'] == $next_step) {
          $sublist[$name] = $project;
        }
      }
      if (!empty($sublist)) {
        $form[$next_step] = [
          '#type' => 'details',
          '#title' => $step_label[0],
          '#description' => $step_label[1],
          '#open' => TRUE,
          '#attributes' => ['class' => ['upgrade-status-next-step']],
          'data' => $this->buildProjectList($sublist, $next_step, $step_label),
          '#tree' => TRUE,
        ];
      }
    }


    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Scan selected'),
      '#weight' => 2,
      '#button_type' => 'primary',
      '#disabled' => !$analyzer_ready,
    ];
    $form['actions']['export'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export selected as HTML'),
      '#weight' => 5,
      '#submit' => [[$this, 'exportReport']],
      '#disabled' => !$analyzer_ready,
    ];
    $form['actions']['export_ascii'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export selected as text'),
      '#weight' => 6,
      '#submit' => [[$this, 'exportReportASCII']],
      '#disabled' => !$analyzer_ready,
    ];

    return $form;
  }

  /**
   * Builds a list and status summary of projects.
   *
   * @param \Drupal\Core\Extension\Extension[] $projects
   *   Array of extensions representing projects.
   * @param string $next_step
   *   The machine name of the suggested next step to take for these projects.
   * @param array $step_label
   *   Labels and other metadata for the step.
   *
   * @return array
   *   Build array.
   */
  protected function buildProjectList(array $projects, string $next_step, array $step_label) {
    $header = [
      'project'  => ['data' => $this->t('Project'), 'class' => 'project-label'],
      'type'     => ['data' => $this->t('Type'), 'class' => 'type-label'],
      'status'   => ['data' => $this->t('Status'), 'class' => 'status-label'],
      'version'  => ['data' => $this->t('Local version'), 'class' => 'version-label'],
      'ready'    => ['data' => $this->t('Local ' . $this->nextMajor . '-ready'), 'class' => 'ready-label'],
      'result'   => ['data' => $this->t('Local scan result'), 'class' => 'scan-info'],
      'updatev'  => ['data' => $this->t('Drupal.org version'), 'class' => 'updatev-info'],
      'update9'  => ['data' => $this->t('Drupal.org ' . $this->nextMajor . '-ready'), 'class' => 'update9-info'],
      'issues'   => ['data' => $this->t('Drupal.org issues'), 'class' => 'issue-info'],
    ];
    $build['list'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#weight' => 20,
      '#options' => [],
    ];
    foreach ($projects as $name => $extension) {
      $option = [
        '#attributes' => ['class' => 'project-' . $name . ' ' . $step_label[3]],
      ];
      $option['project'] = [
        'data' => [
          'label' => [
            '#type' => 'html_tag',
            '#tag' => 'label',
            '#value' => $extension->info['name'] . ' (' . $extension->getName() . ')',
            '#attributes' => [
              'for' => 'edit-' . $next_step . '-data-list-' . str_replace('_', '-', $name),
            ],
          ],
        ],
        'class' => 'project-label',
      ];
      $type = '';
      if ($extension->info['upgrade_status_type'] == ProjectCollector::TYPE_CUSTOM) {
        if ($extension->getType() == 'module') {
          $type = $this->t('Custom module');
        }
        elseif ($extension->getType() == 'theme') {
          $type = $this->t('Custom theme');
        }
        elseif ($extension->getType() == 'profile') {
          $type = $this->t('Custom profile');
        }
      }
      else {
        if ($extension->getType() == 'module') {
          $type = $this->t('Contributed module');
        }
        elseif ($extension->getType() == 'theme') {
          $type = $this->t('Contributed theme');
        }
        elseif ($extension->getType() == 'profile') {
          $type = $this->t('Contributed profile');
        }
      }
      $option['type'] = [
        'data' => [
          'label' => [
            '#type' => 'markup',
            '#markup' => $type,
          ],
        ]
      ];
      $option['status'] = [
        'data' => [
          'label' => [
            '#type' => 'markup',
            '#markup' => empty($extension->status) ? $this->t('Uninstalled') : $this->t('Installed'),
          ],
        ]
      ];

      // Start of local version/readiness columns.
      $option['version'] = [
        'data' => [
          'label' => [
            '#type' => 'markup',
            '#markup' => !empty($extension->info['version']) ? $extension->info['version'] : $this->t('N/A'),
          ],
        ]
      ];
      $option['ready'] = [
        'class' => 'status-info ' . (!empty($extension->info['upgrade_status_next_major_compatible']) ? 'status-info-compatible' : 'status-info-incompatible'),
        'data' => [
          'label' => [
            '#type' => 'markup',
            '#markup' => !empty($extension->info['upgrade_status_next_major_compatible']) ? $this->t('Compatible') : $this->t('Incompatible'),
          ],
        ]
      ];

      $report = $this->projectCollector->getResults($name);
      $result_summary = !empty($report) ? $this->t('No problems found') : $this->t('N/A');
      if (!empty($report['data']['totals']['file_errors'])) {
        $result_summary = $this->formatPlural(
          $report['data']['totals']['file_errors'],
          '@count problem',
          '@count problems'
        );
        $option['result'] = [
          'data' => [
            '#type' => 'link',
            '#title' => $result_summary,
            '#url' => Url::fromRoute('upgrade_status.project', ['project_machine_name' => $name]),
            '#attributes' => [
              'class' => ['use-ajax'],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => Json::encode([
                'width' => 1024,
                'height' => 568,
              ]),
            ],
          ],
          'class' => 'scan-result',
        ];
      }
      else {
        $option['result'] = [
          'data' => [
            'label' => [
              '#type' => 'markup',
              '#markup' => $result_summary,
            ],
          ],
          'class' => 'scan-result',
        ];
      }

      // Start of drupal.org data columns.
      $updatev = $this->t('Not applicable');
      if (!empty($extension->info['upgrade_status_update_link'])) {
        $option['updatev'] = [
          'data' => [
            'link' => [
              '#type' => 'link',
              '#title' => $extension->info['upgrade_status_update_version'],
              '#url' => Url::fromUri($extension->info['upgrade_status_update_link']),
            ],
          ]
        ];
        unset($updatev);
      }
      elseif (!empty($extension->info['upgrade_status_update'])) {
        $updatev = $this->t('Unavailable');
        if ($extension->info['upgrade_status_update'] == ProjectCollector::UPDATE_NOT_CHECKED) {
          $updatev = $this->t('Unchecked');
        }
        elseif ($extension->info['upgrade_status_update'] == ProjectCollector::UPDATE_ALREADY_INSTALLED) {
          $updatev = $this->t('Up to date');
        }
      }
      if (!empty($updatev)) {
        $option['updatev'] = [
          'data' => [
            'label' => [
              '#type' => 'markup',
              '#markup' => $updatev,
            ],
          ]
        ];
      }
      $update_class = 'status-info-na';
      $update_info = $this->t('Not applicable');
      if (isset($extension->info['upgrade_status_update'])) {
        switch ($extension->info['upgrade_status_update']) {
          case ProjectCollector::UPDATE_NOT_AVAILABLE:
            $update_info = $this->t('Unavailable');
            $update_class = 'status-info-na';
            break;
          case ProjectCollector::UPDATE_NOT_CHECKED:
            $update_info = $this->t('Unchecked');
            $update_class = 'status-info-unchecked';
            break;
          case ProjectCollector::UPDATE_AVAILABLE:
          case ProjectCollector::UPDATE_ALREADY_INSTALLED:
            if ($extension->info['upgrade_status_update_compatible']) {
              $update_info = $this->t('Compatible');
              $update_class = 'status-info-compatible';
            }
            else {
              $update_info = $this->t('Incompatible');
              $update_class = 'status-info-incompatible';
            }
            break;
        }
      }
      $option['update9'] = [
        'class' => 'status-info ' . $update_class,
        'data' => [
          'label' => [
            '#type' => 'markup',
            '#markup' => $update_info,
          ],
        ]
      ];
      if ($extension->info['upgrade_status_type'] == ProjectCollector::TYPE_CUSTOM) {
        $option['issues'] = [
          'data' => [
            'label' => [
              '#type' => 'markup',
              '#markup' => $this->t('Not applicable'),
            ],
          ]
        ];
      }
      else {
        $option['issues'] = [
          'data' => [
            'label' => [
              '#type' => 'markup',
              // Use the project name from the info array instead of $key.
              // $key is the local name, not necessarily the project name.
              '#markup' => '<a href="https://drupal.org/project/issues/' . $extension->info['project'] . '?text=Drupal+' . $this->nextMajor . '&status=All">' . $this->t('Issues', [], ['context' => 'Drupal.org issues']) . '</a>',
            ],
          ]
        ];
      }
      $build['list']['#options'][$name] = $option;
    }

    return $build;
  }

  /**
   * Preprocess function to add class to the header row of our table.
   */
  function upgrade_status_preprocess_table_custom_header(array &$element) {
    // Check if this is the table you want to target.
    if (!empty($element['list']['#upgrade_status_step_class'])) {
      // Add class to the header row.
      $element['#header']['#attributes']['class'][] = $element['list']['#upgrade_status_step_class'];
    }
  }

  /**
   * Build a result summary table for quick overview display to users.
   *
   * @param bool|null $environment_status
   *   The status of the environment. Whether to put it into the Fix or Relax
   *   columns or omit it.
   *
   * @return array
   *   Render array.
   */
  protected function buildResultSummary($environment_status = TRUE) {
    $projects = $this->projectCollector->collectProjects();
    $next_steps = $this->projectCollector->getNextStepInfo();

    $last = $this->state->get('update.last_check') ?: 0;
    if ($last == 0) {
      $last_checked = $this->t('Never checked');
    }
    else {
      $time = $this->dateFormatter->formatTimeDiffSince($last);
      $last_checked = $this->t('Last checked @time ago', ['@time' => $time]);
    }
    $update_time = [
      [
        '#type' => 'link',
        '#title' => $this->t('Check available updates'),
        '#url' => Url::fromRoute('update.manual_status', [], ['query' => $this->destination->getAsArray()]),
      ],
      [
        '#type' => 'markup',
        '#markup' => ' (' . $last_checked . ')',
      ],
    ];

    $header = [
      ProjectCollector::SUMMARY_ANALYZE => ['data' => $this->t('Gather data')],
      ProjectCollector::SUMMARY_ACT => ['data' => $this->t('Fix incompatibilities')],
      ProjectCollector::SUMMARY_RELAX => ['data' => $this->t('Relax')],
    ];
    $build = [
      '#type' => 'table',
      '#attributes' => ['class' => ['upgrade-status-of-site']],
      '#header' => $header,
      '#rows' => [
        [
          'data' => [
            ProjectCollector::SUMMARY_ANALYZE => ['data' => []],
            ProjectCollector::SUMMARY_ACT => ['data' => []],
            ProjectCollector::SUMMARY_RELAX => ['data' => []],
          ]
        ]
      ],
    ];
    foreach ($header as $key => $value) {
      $cell_data = $cell_items = [];
      foreach($next_steps as $next_step => $step_label) {
        // If this next step summary belongs in this table cell, collect it.
        if ($step_label[2] == $key) {
          foreach ($projects as $project) {
            if ($project->info['upgrade_status_next'] == $next_step) {
              @$cell_data[$next_step]++;
            }
          }
        }
      }
      if ($key == ProjectCollector::SUMMARY_ANALYZE) {
        // If neither Composer Deploy nor Git Deploy are available and installed, suggest installing one.
        if (empty($projects['git_deploy']->status) && empty($projects['composer_deploy']->status)) {
          $cell_items[] = [
            '#markup' => $this->t('Install <a href=":composer_deploy">Composer Deploy</a> or <a href=":git_deploy">Git Deploy</a> as appropriate for accurate update recommendations', [':composer_deploy' => 'https://drupal.org/project/composer_deploy', ':git_deploy' => 'https://drupal.org/project/git_deploy'])
          ];
        }
        // Add available update info.
        $cell_items[] = $update_time;
      }
      if (($key == ProjectCollector::SUMMARY_ACT) && !is_null($environment_status) && !$environment_status) {
        $cell_items[] = [
          '#markup' => '<a href="#edit-environment">' . $this->t('Environment is incompatible') . '</a>',
        ];
      }

      if (count($cell_data)) {
        foreach ($cell_data as $next_step => $count) {
          $cell_items[] = [
            '#markup' => '<a href="#edit-' . $next_step . '">' . $this->formatPlural($count, '@type: 1 project', '@type: @count projects', ['@type' => $next_steps[$next_step][0]]) . '</a>',
          ];
        }
      }

      if ($key == ProjectCollector::SUMMARY_ANALYZE) {
        $cell_items[] = [
          '#markup' => 'Select any of the projects to rescan as needed below',
        ];
      }
      if ($key == ProjectCollector::SUMMARY_RELAX) {
        // Calculate how done is this site assuming the environment as
        // "one project" for simplicity.
        $done_count = (!empty($cell_data[ProjectCollector::NEXT_RELAX]) ? $cell_data[ProjectCollector::NEXT_RELAX] : 0) + (int) $environment_status;
        $percent = round($done_count / (count($projects) + 1) * 100);
        $build['#rows'][0]['data'][$key]['data'][] = [
          '#type' => 'markup',
          '#allowed_tags' => ['svg', 'path', 'text'],
          '#markup' => <<<MARKUP
        <div class="upgrade-status-result-chart">
        <svg viewBox="0 0 36 36" class="upgrade-status-of-site-circle">
          <path class="circle-bg"
            d="M18 2.0845
              a 15.9155 15.9155 0 0 1 0 31.831
              a 15.9155 15.9155 0 0 1 0 -31.831"
          />
          <path class="circle"
            stroke-dasharray="{$percent}, 100"
            d="M18 2.0845
              a 15.9155 15.9155 0 0 1 0 31.831
              a 15.9155 15.9155 0 0 1 0 -31.831"
          />
          <text x="18" y="20.35" class="percentage">{$percent}%</text>
        </svg>
      </div>
MARKUP
        ];
        if (!empty($environment_status)) {
          $cell_items[] = [
            '#markup' => '<a href="#edit-environment">' . $this->t('Environment checks passed') . '</a>',
          ];
        }
      }
      if (count($cell_items)) {
        $build['#rows'][0]['data'][$key]['data'][] = [
          '#theme' => 'item_list',
          '#items' => $cell_items,
        ];
      }
      else {
        $build['#rows'][0]['data'][$key]['data'][] = [
          '#type' => 'markup',
          '#markup' => $this->t('N/A'),
        ];
      }
    }
    return $build;
  }

  /**
   * Builds a list of environment checks for Drupal 10 compatibility.
   *
   * @return array
   *   Build array. The overall environment status (TRUE, FALSE or NULL) is
   *   indicated in the 'status' key, while a 'description' key explains the
   *   environment requirements on a high level.
   */
  protected function buildEnvironmentChecksFor10() {
    $status = TRUE;
    $header = [
      'requirement' => ['data' => $this->t('Requirement'), 'class' => 'requirement-label'],
      'status' => ['data' => $this->t('Status'), 'class' => 'status-info'],
    ];
    $build['data'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => [],
    ];

    $build['description'] = $this->t('Upgrades to Drupal 10 are supported from Drupal 9.4.x and Drupal 9.5.x. It is suggested to update to the latest Drupal 9 version available. <a href=":platform">Several hosting platform requirements have been raised for Drupal 10</a>.', [':platform' => 'https://www.drupal.org/node/3228686']);

    // Check Drupal version. Link to update if available.
    $core_version_info = [
      '#type' => 'markup',
      '#markup' => $this->t('Version @version.', ['@version' => \Drupal::VERSION]),
    ];
    $has_core_update = FALSE;
    $core_update_info = $this->releaseStore->get('drupal');
    if (isset($core_update_info['releases']) && is_array($core_update_info['releases'])) {
      // Find the latest release that are higher than our current and is not beta/alpha/rc/dev.
      foreach ($core_update_info['releases'] as $version => $release) {
        $major_version = explode('.', $version)[0];
        if ($major_version === '9' && !strpos($version, '-') && (version_compare($version, \Drupal::VERSION) > 0)) {
          $link = $core_update_info['link'] . '/releases/' . $version;
          $core_version_info = [
            '#type' => 'link',
            '#title' => version_compare(\Drupal::VERSION, '9.4.0') >= 0 ?
              $this->t('Version @current allows to upgrade but @new is available.', ['@current' => \Drupal::VERSION, '@new' => $version]) :
              $this->t('Version @current does not allow to upgrade and @new is available.', ['@current' => \Drupal::VERSION, '@new' => $version]),
            '#url' => Url::fromUri($link),
          ];
          $has_core_update = TRUE;
          break;
        }
      }
    }
    if (version_compare(\Drupal::VERSION, '9.4.0') >= 0) {
      if (!$has_core_update) {
        $class = 'color-success';
      }
      else {
        $class = 'color-warning';
      }
    }
    else {
      $status = FALSE;
      $class = 'color-error';
    }
    $build['data']['#rows'][] = [
      'class' => $class,
      'data' => [
        'requirement' => [
          'class' => 'requirement-label',
          'data' => $this->t('Drupal core should be at least 9.4.x'),
        ],
        'status' => [
          'data' => $core_version_info,
          'class' => 'status-info',
        ],
      ]
    ];

    // Check PHP version.
    $version = PHP_VERSION;
    // The value of MINIMUM_PHP in Drupal 10.
    $minimum_php = '8.1.0';
    if (version_compare($version, $minimum_php) >= 0) {
      $class = 'color-success';
    }
    else {
      $class = 'color-error';
      $status = FALSE;
    }
    $build['data']['#rows'][] = [
      'class' => [$class],
      'data' => [
        'requirement' => [
          'class' => 'requirement-label',
          'data' => $this->t('PHP version should be at least @minimum_php. Before updating to PHP @minimum_php, use <code>$ composer why-not php @minimum_php</code> to check if any projects need updating for compatibility. Also check custom projects manually.', ['@minimum_php' => $minimum_php]),
        ],
        'status' => [
          'data' => $this->t('Version @version', ['@version' => $version]),
          'class' => 'status-info',
        ],
      ]
    ];

    // Check database version.
    $database_type = $this->database->databaseType();
    $version = $this->database->version();
    $addendum = '';
    if ($database_type == 'pgsql') {
      $database_type_full_name = 'PostgreSQL';
      $requirement = $this->t('When using PostgreSQL, minimum version is 12 <a href=":trgm">with the pg_trgm extension</a> created.', [':trgm' => 'https://www.postgresql.org/docs/10/pgtrgm.html']);
      $has_trgm = $this->database->query("SELECT installed_version FROM pg_available_extensions WHERE name = 'pg_trgm'")->fetchField();
      if (version_compare($version, '12') >= 0 && $has_trgm) {
        $class = 'color-success';
        $addendum = $this->t('Has pg_trgm extension.');
      }
      else {
        $status = FALSE;
        $class = 'color-error';
        if (!$has_trgm) {
          $addendum = $this->t('No pg_trgm extension.');
        }
      }
      $build['data']['#rows'][] = [
        'class' => [$class],
        'data' => [
          'requirement' => [
            'class' => 'requirement-label',
            'data' => [
              '#type' => 'markup',
              '#markup' => $requirement
            ],
          ],
          'status' => [
            'data' => trim($database_type_full_name . ' ' . $version . ' ' . $addendum),
            'class' => 'status-info',
          ],
        ]
      ];
    }

    // Check JSON support in database.
    $class = 'color-success';
    $requirement = $this->t('Supported.');
    try {
      if (!method_exists($this->database, 'hasJson') || !$this->database->hasJson()) {
        // A hasJson() method was added to Connection from Drupal 9.4.0
        // but we cannot rely on being on Drupal 9.4.x+
        $this->database->query($database_type == 'pgsql' ? 'SELECT JSON_TYPEOF(\'1\')' : 'SELECT JSON_TYPE(\'1\')');
      }
    }
    catch (\Exception $e) {
      $class = 'color-error';
      $status = FALSE;
      $requirement = $this->t('Not supported.');
    }
    $build['data']['#rows'][] = [
      'class' => [$class],
      'data' => [
        'requirement' => [
          'class' => 'requirement-label',
          'data' => $this->t('Database JSON support required'),
        ],
        'status' => [
          'data' => $requirement,
          'class' => 'status-info',
        ],
      ]
    ];

    // Check user roles on the site for invalid permissions.
    $class = 'color-success';
    $requirement = [];
    $user_roles = Role::loadMultiple();
    $all_permissions = array_keys(\Drupal::service('user.permissions')->getPermissions());
    foreach ($user_roles as $role) {
      $role_permissions = $role->getPermissions();
      $valid_role_permissions = array_intersect($role_permissions, $all_permissions);
      $invalid_role_permissions = array_diff($role_permissions, $valid_role_permissions);
      if (!empty($invalid_role_permissions)) {
        $class = 'color-error';
        $status = FALSE;
        $requirement[] = [
          '#theme' => 'item_list',
          '#prefix' => $this->t('Permissions of user role: "@role":', ['@role' => $role->label()]),
          '#items' => $invalid_role_permissions,
        ];
      }
    }
    $build['data']['#rows'][] = [
      'class' => [$class],
      'data' => [
        'requirement' => [
          'class' => 'requirement-label',
          'data' => $this->t('<a href=":url">Invalid permissions will trigger runtime exceptions in Drupal 10.</a> Permissions should be defined in a permissions.yml file or a permission callback.', [':url' => 'https://www.drupal.org/node/3193348']),
        ],
        'status' => [
          'data' => [
            '#theme' => 'item_list',
            '#items' => $requirement,
            '#empty' => $this->t('None found.'),
          ],
          'class' => 'status-info',
        ],
      ]
    ];

    // Check for deprecated or obsolete core extensions.
    $class = 'color-success';
    $requirement = $this->t('None installed.');
    $deprecated_or_obsolete = $this->projectCollector->collectCoreDeprecatedAndObsoleteExtensions();
    if (!empty($deprecated_or_obsolete)) {
      $class = 'color-error';
      $status = FALSE;
      $requirement = join(', ', $deprecated_or_obsolete);
    }
    $build['data']['#rows'][] = [
      'class' => [$class],
      'data' => [
        'requirement' => [
          'class' => 'requirement-label',
          'data' => $this->t('Deprecated or obsolete core extensions installed. These will be removed in the next major version.'),
        ],
        'status' => [
          'data' => [
            '#markup' => $requirement,
          ],
          'class' => 'status-info',
        ],
      ]
    ];

    // Check Drush. We only detect site-local drush for now.
    if (class_exists('\\Drush\\Drush')) {
      $version = call_user_func('\\Drush\\Drush::getMajorVersion');
      if (version_compare($version, '11') >= 0) {
        $class = 'color-success';
      }
      else {
        $status = FALSE;
        $class = 'color-error';
      }
      $label = $this->t('Version @version', ['@version' => $version]);
    }
    else {
      $class = '';
      $label = $this->t('Version cannot be detected, check manually.');
    }
    $build['data']['#rows'][] = [
      'class' => $class,
      'data' => [
        'requirement' => [
          'class' => 'requirement-label',
          'data' => $this->t('When using Drush, minimum version is 11'),
        ],
        'status' => [
          'data' => $label,
          'class' => 'status-info',
        ],
      ]
    ];

    // Save the overall status indicator in the build array. It will be
    // popped off later to be used in the summary table.
    $build['status'] = $status;

    return $build;
  }

  /**
   * Builds a list of environment checks for Drupal 12 compatibility.
   *
   * @return array
   *   Build array. The overall environment status (TRUE, FALSE or NULL) is
   *   indicated in the 'status' key, while a 'description' key explains the
   *   environment requirements on a high level.
   */
  protected function buildEnvironmentChecksFor12() {
    return [
      'description' => $this->t('<a href=":platform">Drupal 12 environment requirements are still to be defined</a>.', [':platform' => 'https://www.drupal.org/project/drupal/issues/3449806']),
      // Checks neither passed, nor failed.
      'status' => NULL,
    ];
  }

  /**
   * Builds a list of environment checks for Drupal 11 compatibility.
   *
   * @return array
   *   Build array. The overall environment status (TRUE, FALSE or NULL) is
   *   indicated in the 'status' key, while a 'description' key explains the
   *   environment requirements on a high level.
   */
  protected function buildEnvironmentChecksFor11() {
    $status = TRUE;
    $header = [
      'requirement' => ['data' => $this->t('Requirement'), 'class' => 'requirement-label'],
      'status' => ['data' => $this->t('Status'), 'class' => 'status-info'],
    ];
    $build['data'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => [],
    ];

    $build['description'] = $this->t('Below are Drupal 11\'s system requirements. If you are working with multiple (dev, stage, live) environments, make sure to check the same requirements there.');

    // Check Drupal version. Link to update if available.
    $core_version_info = [
      '#type' => 'markup',
      '#markup' => $this->t('Version @version.', ['@version' => \Drupal::VERSION]),
    ];
    $has_core_update = FALSE;
    $core_update_info = $this->releaseStore->get('drupal');
    if (isset($core_update_info['releases']) && is_array($core_update_info['releases'])) {
      // Find the latest release that are higher than our current and is not beta/alpha/rc/dev.
      foreach ($core_update_info['releases'] as $version => $release) {
        $major_version = explode('.', $version)[0];
        if ($major_version === '10' && !strpos($version, '-') && (version_compare($version, \Drupal::VERSION) > 0)) {
          $link = $core_update_info['link'] . '/releases/' . $version;
          $core_version_info = [
            '#type' => 'link',
            '#title' => version_compare(\Drupal::VERSION, '10.3.0') >= 0 ?
              $this->t('Version @current allows to upgrade but @new is available.', ['@current' => \Drupal::VERSION, '@new' => $version]) :
              $this->t('Version @current does not allow to upgrade and @new is available.', ['@current' => \Drupal::VERSION, '@new' => $version]),
            '#url' => Url::fromUri($link),
          ];
          $has_core_update = TRUE;
          break;
        }
      }
    }
    if (version_compare(\Drupal::VERSION, '10.3.0') >= 0) {
      if (version_compare(\Drupal::VERSION, '10.4.0') >= 0) {
        $this->messenger()->addWarning('Drupal 11.0 is not a supported upgrade from Drupal 10.4. Make sure to upgrade to 11.1!');
      }
      if (!$has_core_update) {
        $class = 'color-success';
      }
      else {
        $class = 'color-warning';
      }
    }
    else {
      $status = FALSE;
      $class = 'color-error';
    }
    $build['data']['#rows'][] = [
      'class' => $class,
      'data' => [
        'requirement' => [
          'class' => 'requirement-label',
          'data' => $this->t('Drupal core should be at least 10.3.0'),
        ],
        'status' => [
          'data' => $core_version_info,
          'class' => 'status-info',
        ],
      ]
    ];

    // Check PHP version.
    $version = PHP_VERSION;
    $minimum_php = '8.3.0';
    if (version_compare($version, $minimum_php) >= 0) {
      $class = 'color-success';
    }
    else {
      $class = 'color-error';
      $status = FALSE;
    }
    $build['data']['#rows'][] = [
      'class' => [$class],
      'data' => [
        'requirement' => [
          'class' => 'requirement-label',
          'data' => $this->t('PHP version should be at least @minimum_php. Before updating to PHP @minimum_php, use <code>$ composer why-not php @minimum_php</code> to check if any projects need updating for compatibility. Also check custom projects manually.', ['@minimum_php' => $minimum_php]),
        ],
        'status' => [
          'data' => $this->t('Version @version', ['@version' => $version]),
          'class' => 'status-info',
        ],
      ]
    ];

    // Check database version.
    $database_type = $this->database->databaseType();
    $version = $this->database->version();
    $addendum = '';
    if ($database_type == 'mysql') {
      if ($this->database->isMariaDb()) {
        $database_type_full_name = 'MariaDB';
        $requirement = $this->t('When using MariaDB, minimum version is 10.6');
        if (version_compare($version, '10.6') >= 0) {
          $class = 'color-success';
        }
        elseif (version_compare($version, '10.3.7') >= 0) {
          if ($this->moduleHandler->moduleExists('mysql57')) {
            $class = 'color-warning';
            $requirement .= ' ' . $this->t('Keep using <a href=":driver">the MariaDB 10.3 driver</a> for now, which is already installed.', [':driver' => 'https://www.drupal.org/project/mysql57']);
          }
          else {
            $class = 'color-error';
            $requirement .= ' ' . $this->t('Alternatively, <a href=":driver">install the MariaDB 10.3 driver</a> for now.', [':driver' => 'https://www.drupal.org/project/mysql57']);
          }
        }
        else {
          // Should not happen because Drupal 10 already required 10.3.7, but just to be sure.
          $status = FALSE;
          $class = 'color-error';
          $requirement .= ' ' . $this->t('Once updated to at least 10.3.7, you can also <a href=":driver">install the MariaDB 10.3 driver</a> for now.', [':driver' => 'https://www.drupal.org/project/mysql57']);
        }
      }
      else {
        $database_type_full_name = 'MySQL or Percona Server';
        $requirement = $this->t('When using MySQL/Percona, minimum version is 8.0');
        if (version_compare($version, '8.0') >= 0) {
          $class = 'color-success';
        }
        elseif (version_compare($version, '5.7.8') >= 0) {
          if ($this->moduleHandler->moduleExists('mysql57')) {
            $class = 'color-warning';
            $requirement .= ' ' . $this->t('Keep using <a href=":driver">the MySQL 5.7 driver</a> for now, which is already installed.', [':driver' => 'https://www.drupal.org/project/mysql57']);
          }
          else {
            $class = 'color-error';
            $requirement .= ' ' . $this->t('Alternatively, <a href=":driver">install the MySQL 5.7 driver</a> for now.', [':driver' => 'https://www.drupal.org/project/mysql57']);
          }
        }
        else {
          // Should not happen because Drupal 10 already required 5.7.8, but just to be sure.
          $status = FALSE;
          $class = 'color-error';
          $requirement .= ' ' . $this->t('Once updated to at least 5.7.8, you can also <a href=":driver">install the MySQL 5.7 driver</a> for now.', [':driver' => 'https://www.drupal.org/project/mysql57']);
        }
      }
    }
    elseif ($database_type == 'pgsql') {
      $database_type_full_name = 'PostgreSQL';
      $requirement = $this->t('When using PostgreSQL, minimum version is 16 <a href=":trgm">with the pg_trgm extension</a> created.', [':trgm' => 'https://www.postgresql.org/docs/10/pgtrgm.html']);
      $has_trgm = $this->database->query("SELECT installed_version FROM pg_available_extensions WHERE name = 'pg_trgm'")->fetchField();
      if (version_compare($version, '16') >= 0 && $has_trgm) {
        $class = 'color-success';
        $addendum = $this->t('Has pg_trgm extension.');
      }
      else {
        $status = FALSE;
        $class = 'color-error';
        if (!$has_trgm) {
          $addendum = $this->t('No pg_trgm extension.');
        }
      }
    }
    elseif ($database_type == 'sqlite') {
      $database_type_full_name = 'SQLite';
      $minimum_sqlite = '3.45';
      $requirement = $this->t('When using SQLite, minimum version is @minimum_sqlite', ['@minimum_sqlite' => $minimum_sqlite]);
      if (version_compare($version, $minimum_sqlite) >= 0) {
        $class = 'color-success';
      }
      else {
        $status = FALSE;
        $class = 'color-error';
      }
    }

    $build['data']['#rows'][] = [
      'class' => [$class],
      'data' => [
        'requirement' => [
          'class' => 'requirement-label',
          'data' => [
            '#type' => 'markup',
            '#markup' => $requirement
          ],
        ],
        'status' => [
          'data' => $database_type_full_name . ' ' . $version,
          'class' => 'status-info',
        ],
      ]
    ];

    // Check JSON support in database.
    $class = 'color-success';
    $requirement = $this->t('Supported.');
    if (!method_exists($this->database, 'hasJson') || !$this->database->hasJson()) {
      $class = 'color-error';
      $status = FALSE;
      $requirement = $this->t('Not supported.');
    }
    $build['data']['#rows'][] = [
      'class' => [$class],
      'data' => [
        'requirement' => [
          'class' => 'requirement-label',
          'data' => $this->t('Database JSON support required'),
        ],
        'status' => [
          'data' => $requirement,
          'class' => 'status-info',
        ],
      ]
    ];

    // Check for deprecated or obsolete core extensions.
    $class = 'color-success';
    $requirement = $this->t('None installed.');
    $deprecated_or_obsolete = $this->projectCollector->collectCoreDeprecatedAndObsoleteExtensions();
    if (!empty($deprecated_or_obsolete)) {
      $class = 'color-error';
      $status = FALSE;
      $requirement = join(', ', $deprecated_or_obsolete);
    }
    $build['data']['#rows'][] = [
      'class' => [$class],
      'data' => [
        'requirement' => [
          'class' => 'requirement-label',
          'data' => $this->t('Deprecated or obsolete core extensions installed. These will be removed in the next major version.'),
        ],
        'status' => [
          'data' => [
            '#markup' => $requirement,
          ],
          'class' => 'status-info',
        ],
      ]
    ];

    // Check Drush. We only detect site-local drush for now.
    if (class_exists('\\Drush\\Drush')) {
      $version = call_user_func('\\Drush\\Drush::getMajorVersion');
      if (version_compare($version, '13') >= 0) {
        $class = 'color-success';
      }
      else {
        $status = FALSE;
        $class = 'color-error';
      }
      $label = $this->t('Version @version', ['@version' => $version]);
    }
    else {
      $class = '';
      $label = $this->t('Version cannot be detected, check manually.');
    }
    $build['data']['#rows'][] = [
      'class' => $class,
      'data' => [
        'requirement' => [
          'class' => 'requirement-label',
          'data' => $this->t('When using Drush, minimum version is 13'),
        ],
        'status' => [
          'data' => $label,
          'class' => 'status-info',
        ],
      ]
    ];

    // Save the overall status indicator in the build array. It will be
    // popped off later to be used in the summary table.
    $build['status'] = $status;

    return $build;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Reset extension lists for better Drupal 9 compatibility info.
    $this->projectCollector->resetLists();

    $operations = $list = [];
    $projects = $this->projectCollector->collectProjects();
    $submitted = $form_state->getValues();
    $next_steps = $this->projectCollector->getNextStepInfo();
    foreach ($next_steps as $next_step => $step_label) {
      if (!empty($submitted[$next_step]['data']['list'])) {
        foreach ($submitted[$next_step]['data']['list'] as $item) {
          if (isset($projects[$item])) {
            $list[] = $projects[$item];
          }
        }
      }
    }

    // It is not possible to make an HTTP request to this same webserver
    // if the host server is PHP itself, because it is single-threaded.
    // See https://www.php.net/manual/en/features.commandline.webserver.php
    $use_http = php_sapi_name() != 'cli-server';
    $php_server = !$use_http;
    if ($php_server) {
      // Log the selected processing method for project support purposes.
      $this->logger->notice('Starting Upgrade Status on @count projects without HTTP sandboxing because the built-in PHP webserver does not allow for that.', ['@count' => count($list)]);
    }
    else {
      // Attempt to do an HTTP request to the frontpage of this Drupal instance.
      // If that does not work then we'll not be able to process projects over
      // HTTP. Processing projects directly is less safe (in case of PHP fatal
      // errors the batch process may halt), but we have no other choice here
      // but to take a chance.
      list(, $message, $data) = static::doHttpRequest('upgrade_status_request_test', 'upgrade_status_request_test');
      if (empty($data) || !is_array($data) || ($data['message'] != 'Request test success')) {
        $use_http = FALSE;
        $this->logger->notice('Starting Upgrade Status on @count projects without HTTP sandboxing. @error', ['@error' => $message, '@count' => count($list)]);
      }
    }

    if ($use_http) {
      // Log the selected processing method for project support purposes.
      $this->logger->notice('Starting Upgrade Status on @count projects with HTTP sandboxing.', ['@count' => count($list)]);
    }

    foreach ($list as $item) {
      $operations[] = [
        static::class . '::parseProject',
        [$item, $use_http]
      ];
    }
    if (!empty($operations)) {
      // Allow other modules to alter the operations to be run.
      $this->moduleHandler->alter('upgrade_status_operations', $operations, $form_state);
    }
    if (!empty($operations)) {
      $batch = [
        'title' => $this->t('Scanning projects'),
        'operations' => $operations,
        'finished' => static::class . '::finishedParsing',
      ];
      batch_set($batch);
    }
    else {
      $this->messenger()->addError('No projects selected to scan.');
    }
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $format
   *   Either 'html' or 'ascii' depending on what the format should be.
   */
  public function exportReport(array &$form, FormStateInterface $form_state, string $format = 'html') {
    $extensions = [];
    $projects = $this->projectCollector->collectProjects();
    $submitted = $form_state->getValues();
    $next_steps = $this->projectCollector->getNextStepInfo();
    foreach ($next_steps as $next_step => $step_label) {
      if (!empty($submitted[$next_step]['data']['list'])) {
        foreach ($submitted[$next_step]['data']['list'] as $item) {
          if (isset($projects[$item])) {
            $type = $projects[$item]->info['upgrade_status_type'] == ProjectCollector::TYPE_CUSTOM ? 'custom' : 'contrib';
            $extensions[$type][$item] =
              $format == 'html' ?
                $this->resultFormatter->formatResult($projects[$item]) :
                $this->resultFormatter->formatAsciiResult($projects[$item]);
          }
        }
      }
    }

    if (empty($extensions)) {
      $this->messenger()->addError('No projects selected to export.');
      return;
    }

    $build = [
      '#theme' => 'upgrade_status_'. $format . '_export',
      '#projects' => $extensions
    ];

    $fileDate = $this->resultFormatter->formatDateTime(0, 'html_datetime');
    $extension = $format == 'html' ? '.html' : '.txt';
    $filename = 'upgrade-status-export-' . $fileDate . $extension;

    $response = new Response($this->renderer->renderRoot($build));
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
    $form_state->setResponse($response);
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function exportReportASCII(array &$form, FormStateInterface $form_state) {
    $this->exportReport($form, $form_state, 'ascii');
  }

  /**
   * Batch callback to analyze a project.
   *
   * @param \Drupal\Core\Extension\Extension $extension
   *   The extension to analyze.
   * @param bool $use_http
   *   Whether to use HTTP to execute the processing or execute locally. HTTP
   *   processing could fail in some container setups. Local processing may
   *   fail due to timeout or memory limits.
   * @param array $context
   *   Batch context.
   */
  public static function parseProject(Extension $extension, $use_http, &$context) {
    $context['message'] = t('Analysis complete for @project.', ['@project' => $extension->getName()]);

    if (!$use_http) {
      \Drupal::service('upgrade_status.deprecation_analyzer')->analyze($extension);
      return;
    }

    // Do the HTTP request to run processing.
    list($error, $message) = static::doHttpRequest($extension->getName());

    if ($error !== FALSE) {
      /** @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface $key_value */
      $key_value = \Drupal::service('keyvalue')->get('upgrade_status_scan_results');

      $result = [];
      $result['date'] = \Drupal::time()->getRequestTime();
      $result['data'] = [
        'totals' => [
          'errors' => 1,
          'file_errors' => 1,
          'upgrade_status_split' => [
            'warning' => 1,
          ]
        ],
        'files' => [],
      ];
      $result['data']['files'][$error] = [
        'errors' => 1,
        'messages' => [
          [
            'message' => $message,
            'line' => 0,
          ],
        ],
      ];

      $key_value->set($extension->getName(), $result);
    }
  }

  /**
   * Batch callback to finish parsing.
   *
   * @param $success
   *   TRUE if the batch operation was successful; FALSE if there were errors.
   * @param $results
   *   An associative array of results from the batch operation.
   */
  public static function finishedParsing($success, $results) {
    $logger = \Drupal::logger('upgrade_status');
    if ($success) {
      $logger->notice('Finished Upgrade Status processing successfully.');
    }
    else {
      $logger->notice('Finished Upgrade Status processing with errors.');
    }
  }

  /**
   * Do an HTTP request with the type and machine name.
   *
   * @param string $project_machine_name
   *   The machine name of the project.
   *
   * @return array
   *   A three item array with any potential errors, the error message and the
   *   returned data as the third item. Either of them will be FALSE if they are
   *   not applicable. Data may also be NULL if response JSON decoding failed.
   */
  public static function doHttpRequest(string $project_machine_name) {
    $error = $message = $data = FALSE;

    // Prepare for a POST request to scan this project. The separate HTTP
    // request is used to separate any PHP errors found from this batch process.
    // We can store any errors and gracefully continue if there was any PHP
    // errors in parsing.
    $url = Url::fromRoute(
      'upgrade_status.analyze',
      [
        'project_machine_name' => $project_machine_name
      ]
    );

    // Pass over authentication information because access to this functionality
    // requires administrator privileges.
    /** @var \Drupal\Core\Session\SessionConfigurationInterface $session_config */
    $session_config = \Drupal::service('session_configuration');
    $request = \Drupal::request();
    $session_options = $session_config->getOptions($request);
    // Unfortunately DrupalCI testbot does not have a domain that would normally
    // be considered valid for cookie setting, so we need to work around that
    // by manually setting the cookie domain in case there was none. What we
    // care about is we get actual results, and cookie on the host level should
    // suffice for that.
    $cookie_domain = empty($session_options['cookie_domain']) ? '.' . $request->getHost() : $session_options['cookie_domain'];
    $cookie_jar = new CookieJar();
    $cookie = new SetCookie([
      'Name' => $session_options['name'],
      'Value' => $request->cookies->get($session_options['name']),
      'Domain' => $cookie_domain,
      'Secure' => $session_options['cookie_secure'],
    ]);
    $cookie_jar->setCookie($cookie);
    $options = [
      'cookies' => $cookie_jar,
      'timeout' => 0,
    ];

    // Try a POST request with the session cookie included. We expect valid JSON
    // back. In case there was a PHP error before that, we log that.
    try {
      $response = \Drupal::httpClient()->post($url->setAbsolute()->toString(), $options);
      $data = json_decode((string) $response->getBody(), TRUE);
      if (!$data) {
        $error = 'PHP Fatal Error';
        $message = (string) $response->getBody();
      }
    }
    catch (\Exception $e) {
      $error = 'Scanning exception';
      $message = $e->getMessage();
    }

    return [$error, $message, $data];
  }

  /**
   * Checks config directory settings for use of deprecated values.
   *
   * The $config_directories variable is deprecated in Drupal 8. However,
   * the Settings object obscures the fact in Settings:initialize(), where
   * it throws an error but levels the values in the deprecated location
   * and $settings. So after that, it is not possible to tell if either
   * were set in settings.php or not.
   *
   * Therefore we reproduce loading of settings and check the raw values.
   *
   * @return bool|NULL
   *   TRUE if the deprecated setting is used. FALSE if not used.
   *   NULL if both values are used.
   */
  protected function isDeprecatedConfigDirectorySettingUsed() {
    $app_root = $this->kernel->getAppRoot();
    $site_path = $this->kernel->getSitePath();
    if (is_readable($app_root . '/' . $site_path . '/settings.php')) {
      // Reset the "global" variables expected to exist for settings.
      $settings = [];
      $config = [];
      $databases = [];
      $class_loader = require $app_root . '/autoload.php';
      require $app_root . '/' . $site_path . '/settings.php';
    }

    if (!empty($config_directories)) {
      if (!empty($settings['config_sync_directory'])) {
        // Both are set. The $settings copy will prevail in Settings::initialize().
        return NULL;
      }
      // Only the deprecated variable is set.
      return TRUE;
    }

    // The deprecated variable is not set.
    return FALSE;
  }

  /**
   * Dynamic page title for the form to make the status target clear.
   */
  public function getTitle() {
    return $this->t('Drupal @version upgrade status', ['@version' => $this->nextMajor]);
  }

}
