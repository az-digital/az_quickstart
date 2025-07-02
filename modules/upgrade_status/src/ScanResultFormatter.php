<?php

namespace Drupal\upgrade_status;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use dekor\ArrayToTextTable;

/**
 * Format scan results for display or export.
 */
class ScanResultFormatter {

  use StringTranslationTrait;

  /**
   * Upgrade status scan result storage.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $scanResultStorage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a \Drupal\upgrade_status\Controller\ScanResultFormatter.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The key/value factory.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    KeyValueFactoryInterface $key_value_factory,
    DateFormatterInterface $dateFormatter,
    TimeInterface $time,
    ModuleHandlerInterface $module_handler
  ) {
    $this->scanResultStorage = $key_value_factory->get('upgrade_status_scan_results');
    $this->dateFormatter = $dateFormatter;
    $this->time = $time;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('keyvalue'),
      $container->get('date.formatter'),
      $container->get('datetime.time'),
      $container->get('module_handler')
    );
  }

  /**
   * Get scanning result for an extension.
   *
   * @param \Drupal\Core\Extension\Extension $extension
   *   Drupal extension object.
   * @return null|array
   *   Scan results array or null if no scan results are saved.
   */
  public function getRawResult(Extension $extension) {
    return $this->scanResultStorage->get($extension->getName()) ?: NULL;
  }

  /**
   * Format results output for an extension.
   *
   * @param \Drupal\Core\Extension\Extension $extension
   *   Drupal extension object.
   *
   * @return array
   *   Build array.
   */
  public function formatResult(Extension $extension) {
    $result = $this->getRawResult($extension);
    $info = $extension->info;
    $label = $info['name'] . (!empty($info['version']) ? ' ' . $info['version'] : '');

    // This project was not yet scanned or the scan results were removed.
    if (empty($result)) {
      return [
        '#title' => $label,
        'result' => [
          '#type' => 'markup',
          '#markup' => $this->t(
            'No deprecation scanning data available. <a href="@url">Go to the Upgrade Status form</a>.',
            [
              '@url' => Url::fromRoute('upgrade_status.report')->toString()
            ]
          ),
        ],
      ];
    }

    if (isset($result['data']['totals'])) {
      $project_error_count = $result['data']['totals']['file_errors'];
    }
    else {
      $project_error_count = 0;
    }

    $build = [
      '#attached' => ['library' => ['upgrade_status/upgrade_status.admin']],
      '#title' => $label,
      'date' => [
        '#type' => 'markup',
        '#markup' => '<div class="list-description">' . $this->t('Scanned on @date.', ['@date' => $this->dateFormatter->format($result['date'])]) . '</div>',
        '#weight' => -10,
      ],
    ];

    // If this project had no known issues found, report that.
    if ($project_error_count === 0) {
      $build['data'] = [
        '#type' => 'markup',
        '#markup' => $this->t('No known issues found.'),
        '#weight' => 5,
      ];
      return $build;
    }

    // Otherwise prepare list of errors in groups.
    $groups = [];
    foreach ($result['data']['files'] as $filepath => $errors) {
      foreach ($errors['messages'] as $error) {

        // Remove the Drupal root directory. If this is a composer setup, then
        // the webroot is in a web/ directory, add that back in for easy path
        // copy-pasting.
        $short_path = str_replace(DRUPAL_ROOT . '/', '', $filepath);
        if (preg_match('!/web$!', DRUPAL_ROOT)) {
          $short_path = 'web/' . $short_path;
        }
        // Allow paths and namespaces to wrap. Emphasize filename as it may
        // show up in the middle of the info
        $short_path = str_replace('/', '/<wbr>', $short_path);
        if (strpos($short_path, 'in context of')) {
          $short_path = preg_replace('!/([^/]+)( \(in context of)!', '/<strong>\1</strong>\2', $short_path);
          $short_path = str_replace('\\', '\\<wbr>', $short_path);
        }
        else {
          $short_path = preg_replace('!/([^/]+)$!', '/<strong>\1</strong>', $short_path);
        }

        // @todo could be more accurate with reflection but not sure it is even possible as the reflected
        //   code may not be in the runtime at this point (eg. functions in include files)
        //   see https://www.php.net/manual/en/reflectionfunctionabstract.getfilename.php
        //   see https://www.php.net/manual/en/reflectionclass.getfilename.php

        // Link to documentation for a function in this specific Drupal version.
        $api_version = preg_replace('!^(8\.\d+)\..+$!', '\1', \Drupal::VERSION) . '.x';
        $api_link = 'https://api.drupal.org/api/drupal/' . $api_version . '/search/';
        $formatted_error = preg_replace('!deprecated function ([^(]+)\(\)!', 'deprecated function <a target="_blank" href="' . $api_link . '\1">\1()</a>', $error['message']);

        // Replace deprecated class links.
        if (preg_match('!class (Drupal\\\\\S+)\.( |$)!', $formatted_error, $found)) {
          if (preg_match('!Drupal\\\\([a-z_0-9A-Z]+)\\\\(.+)$!', $found[1], $namespace)) {

            $path_parts = explode('\\', $namespace[2]);
            $class = array_pop($path_parts);
            if (in_array($namespace[1], ['Component', 'Core'])) {
              $class_file = 'core!lib!Drupal!' . $namespace[1];
            }
            elseif (in_array($namespace[1], ['KernelTests', 'FunctionalTests', 'FunctionalJavascriptTests', 'Tests'])) {
              $class_file = 'core!tests!Drupal!' . $namespace[1];
            }
            else {
              $class_file = 'core!modules!' . $namespace[1] . '!src';
            }

            if (count($path_parts)) {
              $class_file .= '!' . join('!', $path_parts);
            }

            $class_file .= '!' . $class . '.php';
            $api_link = 'https://api.drupal.org/api/drupal/' . $class_file . '/class/' . $class . '/' . $api_version;
            $formatted_error = str_replace($found[1], '<a target="_blank" href="' . $api_link . '">' . $found[1] . '</a>', $formatted_error);
          }
        }

        // Allow error messages to wrap.
        $formatted_error = str_replace('\\', '\\<wbr>', $formatted_error);

        // Make drupal.org documentation links clickable.
        $formatted_error = preg_replace('!See (https://(www.)?drupal.org\S*?)(\.|\s|$)!', 'See <a href="\1">\1</a>\3', $formatted_error);

        // Format core_version_requirement message.
        $formatted_error = preg_replace('!(core_version_requirement: .+) (to designate|is not)!', '<code>\1</code> \2', $formatted_error);

        $category = 'uncategorized';
        if (!empty($error['upgrade_status_category'])) {
          if (in_array($error['upgrade_status_category'], ['safe', 'old'])) {
            $category = 'now';
          }
          else {
            $category = $error['upgrade_status_category'];
          }
        }
        @$groups[$category][] = [
          'filename' => [
            '#type' => 'markup',
            '#markup' => $short_path,
            '#wrapper_attributes' => [
              'class' => ['status-info'],
            ]
          ],
          'line' => [
            '#type' => 'markup',
            '#markup' => $error['line'],
          ],
          'issue' => [
            '#type' => 'markup',
            '#markup' => $formatted_error,
          ],
        ];
      }
    }

    $build['groups'] = [
      '#weight' => 100,
    ];
    $group_help = [
      'rector' => [
        $this->t('Fix now with automation'),
        'color-warning rector-covered',
        $this->t('Avoid some manual work by using <a href="@drupal-rector">drupal-rector to fix issues automatically</a>.', ['@drupal-rector' => 'https://www.drupal.org/project/rector']),
      ],
      'now' => [
        $this->t('Fix now manually'),
        'color-error',
        $this->t('It does not seem like these are covered by automation yet. <a href="@drupal-rector">Contribute to drupal-rector to provide coverage</a>. Fix manually in the meantime.', ['@drupal-rector' => 'https://www.drupal.org/project/rector']),
      ],
      'uncategorized' => [
        $this->t('Check manually'),
        'color-warning',
        $this->t('Errors without Drupal source version numbers including parse errors and use of APIs from dependencies.'),
      ],
      'later' => [
        $this->t('Fix later'),
        'color-warning known-later',
        // Issues to fix later need different guidance based on whether they
        // were found in a contributed project or a custom project.
        !empty($extension->info['project']) ?
          $this->t('Based on the Drupal deprecation version number of these, fixing them may make the contributed project incompatible with supported Drupal core versions.') :
          $this->t('Based on the Drupal deprecation version number of these, fixing them will likely make them incompatible with your current Drupal version.')
      ],
      'ignore' => [
        $this->t('Ignore'),
        'color-warning known-ignore',
        $this->t('Deprecated API use for APIs removed in future Drupal major versions is not required to fix yet.'),
      ],
    ];
    foreach ($group_help as $group_key => $group_info) {
      if (empty($groups[$group_key])) {
        // Skip this group if there was no error to display.
        continue;
      }
      $build['groups'][$group_key] = [
        '#prefix' => '<div class="upgrade-status-project-result-group">',
        '#suffix' => '</div>',
        'title' => [
          '#type' => 'markup',
          '#markup' => '<h3>' . $group_info[0] . '</h3>',
        ],
        'description' => [
          '#type' => 'markup',
          '#markup' => '<div class="description">' . $group_info[2] . '</div>',
        ],
        'errors' => [
          '#type' => 'table',
          '#header' => [
            'filename' => $this->t('File name'),
            'line' => $this->t('Line'),
            'issue' => $this->t('Error'),
          ],
        ],
      ];
      foreach ($groups[$group_key] as $item) {
        $item['#attributes']['class'] = [$group_info[1]];
        $build['groups'][$group_key]['errors'][] = $item;
      }
      // All modules (thinking of Upgrade Rector here primarily) to alter
      // results display.
      $this->moduleHandler->alter('upgrade_status_result', $build['groups'][$group_key], $extension, $group_key);
    }

    $summary = [];
    if (!empty($result['data']['totals']['upgrade_status_split']['error'])) {
      $summary[] = $this->formatPlural($result['data']['totals']['upgrade_status_split']['error'], '@count error found.', '@count errors found.');
    }
    if (!empty($result['data']['totals']['upgrade_status_split']['warning'])) {
      $summary[] = $this->formatPlural($result['data']['totals']['upgrade_status_split']['warning'], '@count warning found.', '@count warnings found.');
    }
    $build['summary'] = [
      '#type' => '#markup',
      '#markup' => '<div class="list-description">' . join(' ', $summary) . '</div>',
      '#weight' => 5,
    ];

    $build['export'] = [
      '#type' => 'link',
      '#title' => $this->t('Export as HTML'),
      '#name' => 'export',
      '#url' => Url::fromRoute(
        'upgrade_status.export',
        [
          'type' => $extension->getType(),
          'project_machine_name' => $extension->getName(),
          'format' => 'html',
        ]
      ),
      '#attributes' => [
        'class' => [
          'button',
          'button--primary',
        ],
      ],
      '#weight' => 200,
    ];

    $build['export_ascii'] = [
      '#type' => 'link',
      '#title' => $this->t('Export as text'),
      '#name' => 'export_ascii',
      '#url' => Url::fromRoute(
        'upgrade_status.export',
        [
          'type' => $extension->getType(),
          'project_machine_name' => $extension->getName(),
          'format' => 'ascii',
        ]
      ),
      '#attributes' => [
        'class' => [
          'button',
          'button--primary',
        ],
      ],
      '#weight' => 200,
    ];

    return $build;
  }

  /**
   * Format results output for an extension as ASCII.
   *
   * @return array
   *   Build array.
   */
  public function formatAsciiResult(Extension $extension) {
    $result = $this->getRawResult($extension);
    $info = $extension->info;
    $label = $info['name'] . (!empty($info['version']) ? ' ' . $info['version'] : '');

    // This project was not yet scanned or the scan results were removed.
    if (empty($result)) {
      return [
        '#title' => $label,
        'data' => [
          '#type' => 'markup',
          '#markup' => $this->t('No deprecation scanning data available.'),
        ],
      ];
    }

    if (isset($result['data']['totals'])) {
      $project_error_count = $result['data']['totals']['file_errors'];
    }
    else {
      $project_error_count = 0;
    }

    $build = [
      '#title' => $label,
      'date' => [
        '#type' => 'markup',
        '#markup' =>  wordwrap($this->t('Scanned on @date.', ['@date' => $this->dateFormatter->format($result['date'])]), 80, "\n", true),
        '#weight' => -10,
      ],
    ];

    // If this project had no known issues found, report that.
    if ($project_error_count === 0) {
      $build['data'] = [
        '#type' => 'markup',
        '#markup' => $this->t('No known issues found.'),
        '#weight' => 5,
      ];
      return $build;
    }

    // Otherwise prepare list of errors in tables.
    $tables = '';

    $hasFixRector = FALSE;
    foreach ($result['data']['files'] as $filepath => $errors) {
      // Remove the Drupal root directory name. If this is a composer setup,
      // then the webroot is in a web/ directory, add that back in for easy
      // path copy-pasting.
      $short_path = str_replace(DRUPAL_ROOT . '/', '', $filepath);
      if (preg_match('!/web$!', DRUPAL_ROOT)) {
        $short_path = 'web/' . $short_path;
      }
      $short_path = wordwrap($short_path, 80, "\n", TRUE);
      $tables .= $short_path . ":\n";

      $table = [];
      foreach ($errors['messages'] as $error) {
        $level_label = $this->t('Check manually');
        if (!empty($error['upgrade_status_category'])) {
          if ($error['upgrade_status_category'] == 'ignore') {
            $level_label = $this->t('Ignore');
          }
          elseif ($error['upgrade_status_category'] == 'later') {
            $level_label = $this->t('Fix later');
          }
          elseif (in_array($error['upgrade_status_category'], ['safe', 'old'])) {
            $level_label = $this->t('Fix now');
          }
          elseif ($error['upgrade_status_category'] == 'rector') {
            $level_label = $this->t('Fix with rector');
            $hasFixRector = TRUE;
          }
        }

        $message = str_replace("\n", ' ', $error['message']);
        $table[] = [
          'status' => wordwrap($level_label, 8, "\n", true),
          'line' => wordwrap($error['line'], 7, "\n", true),
          'message' => wordwrap($message . "\n", 60, "\n", true)
        ];
      }
      $asciiRenderer = new ArrayToTextTable($table);
      $tables .= $asciiRenderer->render() . "\n";
    }
    $build['data'] = $tables;

    $summary = [];
    if (!empty($result['data']['totals']['upgrade_status_split']['error'])) {
      $summary[] = $this->formatPlural($result['data']['totals']['upgrade_status_split']['error'], '@count error found.', '@count errors found.');
    }
    if (!empty($result['data']['totals']['upgrade_status_split']['warning'])) {
      $summary[] = $this->formatPlural($result['data']['totals']['upgrade_status_split']['warning'], '@count warning found.', '@count warnings found.');
    }
    if ($hasFixRector) {
      $summary[] = $this->t('Avoid some manual work by using drupal-rector for fixing issues automatically or Upgrade Rector to generate patches.');
    }
    $build['summary'] = [
      '#type' => '#markup',
      '#markup' => wordwrap(join(' ', $summary), 80, "\n", true),
      '#weight' => 5,
    ];

    return $build;
  }

  /**
   * Format date/time.
   *
   * @param int $time
   *   (optional) Timestamp. Current time used if not specified.
   * @param string $format
   *   (optional) Format identifier. Default format is used it not specified.
   *
   * @return string
   *   Formatted date/time.
   */
  public function formatDateTime($time = 0, $format = '') {
    if (empty($time)) {
      $time = $this->time->getCurrentTime();
    }
    return $this->dateFormatter->format($time, $format);
  }

}
